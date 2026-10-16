<?php
// ─── PLACE ORDER (shared by POS and customer checkout) ─────────────
// The browser only says WHICH products and HOW MANY. Prices and the
// total are always taken from the database, so nobody can change what
// they pay by editing the request.
//
// Everything runs in one transaction: the order, its items and the stock
// deduction either all happen or none do. Products are locked while we
// check stock, so two people can't both buy the last item.

/**
 * @param array $cartItems  [['id' => productId, 'qty' => n], ...] from the browser
 * @param array $order      column => value for the orders table (except total)
 * @return array            [true, ['order_id' => .., 'total' => ..]] or [false, 'message']
 */
function place_order(mysqli $conn, $cartItems, array $order): array
{
    // 1. Clean up the cart: whole numbers only, merge duplicate lines.
    if (!is_array($cartItems) || !$cartItems) return [false, 'Your cart is empty'];
    $wanted = [];
    foreach ($cartItems as $item) {
        $pid = (int)($item['id'] ?? 0);
        $qty = (int)($item['qty'] ?? 0);
        if ($pid <= 0 || $qty <= 0 || $qty > 10000) return [false, 'Invalid item in cart'];
        $wanted[$pid] = ($wanted[$pid] ?? 0) + $qty;
    }

    $conn->begin_transaction();
    try {
        // 2. Lock these products and read their real prices and stock.
        $ids  = array_keys($wanted);
        $ph   = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $conn->prepare("SELECT id, name, price, stock FROM products WHERE id IN ($ph) FOR UPDATE");
        $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
        $stmt->execute();
        $products = [];
        foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $p) $products[(int)$p['id']] = $p;

        // 3. Check every line and compute the total from DB prices.
        $total = 0.0;
        foreach ($wanted as $pid => $qty) {
            if (!isset($products[$pid])) {
                $conn->rollback();
                return [false, 'A product in your cart is no longer available'];
            }
            if ((int)$products[$pid]['stock'] < $qty) {
                $conn->rollback();
                $left = max(0, (int)$products[$pid]['stock']);
                return [false, "Only $left left of \"{$products[$pid]['name']}\""];
            }
            $total += (float)$products[$pid]['price'] * $qty;
        }
        $total = round($total, 2);

        // 4. Insert the order.
        $order['total'] = $total;
        $cols  = array_keys($order);
        $types = '';
        foreach ($order as $v) $types .= is_int($v) ? 'i' : (is_float($v) ? 'd' : 's');
        $sql  = 'INSERT INTO orders (' . implode(', ', $cols) . ') VALUES (' . implode(', ', array_fill(0, count($cols), '?')) . ')';
        $ins  = $conn->prepare($sql);
        $vals = array_values($order);
        $ins->bind_param($types, ...$vals);
        $ins->execute();
        $order_id = $ins->insert_id;

        // 5. Items + stock.
        $item = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        $dec  = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
        foreach ($wanted as $pid => $qty) {
            $price = (float)$products[$pid]['price'];
            $item->bind_param("iiid", $order_id, $pid, $qty, $price);
            $item->execute();
            $dec->bind_param("ii", $qty, $pid);
            $dec->execute();
        }

        $conn->commit();
        return [true, ['order_id' => $order_id, 'total' => $total]];
    } catch (Throwable $e) {
        $conn->rollback();
        error_log('place_order failed: ' . $e->getMessage());
        return [false, 'Failed to save order'];
    }
}
