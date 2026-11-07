<?php
/**
 * View: Account Orders
 * Variables: $orders, $cart_count, $current_page, $total_pages, $total_items
 */
?>

<section class="page-header">
    <h1>My Orders &middot; ትዕዛዞቼ</h1>
    <p class="amharic-subtitle"><?= (int) $total_items ?> order<?= $total_items !== 1 ? 's' : '' ?> placed</p>
</section>

<div class="account-layout">
    <!-- Sidebar -->
    <aside class="account-sidebar">
        <h3>Menu</h3>
        <ul>
            <li><a href="/account">Dashboard</a></li>
            <li><a href="/orders" class="active">My Orders</a></li>
            <li><a href="/subscriptions">Subscriptions</a></li>
            <li><a href="/account#profile">Edit Profile</a></li>
            <li><a href="/logout">Logout</a></li>
        </ul>
    </aside>

    <!-- Main Content -->
    <div>
        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">&#128230;</div>
                <h2>No Orders Yet</h2>
                <p>You haven't placed any orders. Start shopping for fresh injera!</p>
                <a href="/products" class="btn btn-primary">Browse Products</a>
            </div>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Order Number</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>
                                <strong style="color: var(--gold);"><?= htmlspecialchars($order['order_number']) ?></strong>
                            </td>
                            <td style="color: var(--text-muted);"><?= formatDate($order['created_at']) ?></td>
                            <td>
                                <span class="badge status-<?= $order['status'] ?>">
                                    <?= ucfirst(str_replace('_', ' ', $order['status'])) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge" style="background: rgba(198,183,155,0.08); color: var(--text-muted);">
                                    <?= ucfirst(str_replace('_', ' ', $order['payment_status'])) ?>
                                </span>
                            </td>
                            <td style="font-weight: 700;"><?= formatPrice((float) $order['total']) ?></td>
                            <td>
                                <a href="/orders/<?= (int) $order['id'] ?>" class="btn btn-secondary" style="padding: 6px 14px; font-size: 0.78rem;">
                                    View
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($current_page > 1): ?>
                    <a href="/orders?page=<?= $current_page - 1 ?>">&laquo; Prev</a>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if ($i === $current_page): ?>
                        <span class="active"><?= $i ?></span>
                    <?php else: ?>
                        <a href="/orders?page=<?= $i ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                <?php if ($current_page < $total_pages): ?>
                    <a href="/orders?page=<?= $current_page + 1 ?>">Next &raquo;</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
