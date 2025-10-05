<?php
/**
 * Admin Table Components
 * Modular functions for rendering admin panel tables
 */

/**
 * Render products table rows
 */
function renderProductsTable($products) {
    if (empty($products)) {
        echo '<tr><td colspan="7" class="text-center">No products found</td></tr>';
        return;
    }
    
    foreach ($products as $product) {
        $imageUrl = $product['image_url'] ?? $product['image_path'] ?? 'assets/img/placeholder.svg';
        $productName = htmlspecialchars($product['name'] ?? '');
        $price = number_format($product['price'] ?? 0, 2);
        $stock = $product['stock_quantity'] ?? $product['stock'] ?? 0;
        $category = htmlspecialchars($product['category'] ?? $product['category_name'] ?? '');
        $statusClass = $product['is_active'] ? 'success' : 'secondary';
        $statusText = $product['is_active'] ? 'Active' : 'Inactive';
        ?>
        <tr data-product-id="<?= $product['id'] ?>">
            <td>
                <img src="<?= htmlspecialchars($imageUrl) ?>" 
                     alt="<?= $productName ?>" 
                     class="img-thumbnail admin-product-thumbnail">
            </td>
            <td class="product-name"><?= $productName ?></td>
            <td class="product-price">$<?= $price ?></td>
            <td class="product-stock"><?= $stock ?></td>
            <td class="product-category"><?= $category ?></td>
            <td class="product-status">
                <span class="badge bg-<?= $statusClass ?>"><?= $statusText ?></span>
            </td>
            <td class="product-actions">
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-primary edit-product" 
                            data-product-id="<?= $product['id'] ?>"
                            data-product-name="<?= htmlspecialchars($product['name'] ?? '') ?>"
                            data-product-price="<?= $product['price'] ?? 0 ?>"
                            data-product-stock="<?= $product['stock_quantity'] ?? $product['stock'] ?? 0 ?>"
                            data-product-category="<?= $product['category_id'] ?? '' ?>"
                            data-product-description="<?= htmlspecialchars($product['description'] ?? '') ?>"
                            data-product-sku="<?= htmlspecialchars($product['sku'] ?? '') ?>"
                            data-product-image="<?= htmlspecialchars($imageUrl) ?>"
                            data-product-active="<?= $product['is_active'] ? 1 : 0 ?>"
                            title="Edit Product">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <form method="POST" action="admin.php" style="display: inline;" 
                          onsubmit="return confirm('Are you sure you want to delete this product?');">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                        <input type="hidden" name="action" value="delete_product">
                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm" title="Delete Product">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                </div>
            </td>
        </tr>
        <?php
    }
}

/**
 * Render categories table rows
 */
function renderCategoriesTable($categories) {
    if (empty($categories)) {
        echo '<tr><td colspan="5" class="text-center">No categories found</td></tr>';
        return;
    }
    
    foreach ($categories as $category) {
        $categoryName = htmlspecialchars($category['name'] ?? '');
        $description = htmlspecialchars($category['description'] ?? '');
        $productCount = $category['product_count'] ?? 0;
        $statusClass = $category['is_active'] ? 'success' : 'secondary';
        $statusText = $category['is_active'] ? 'Active' : 'Inactive';
        ?>
        <tr data-category-id="<?= $category['id'] ?>">
            <td class="category-name"><?= $categoryName ?></td>
            <td class="category-description"><?= $description ?: '-' ?></td>
            <td class="category-product-count"><?= $productCount ?></td>
            <td class="category-status">
                <span class="badge bg-<?= $statusClass ?>"><?= $statusText ?></span>
            </td>
            <td class="category-actions">
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-primary edit-category" 
                            data-category-id="<?= $category['id'] ?>"
                            data-category-name="<?= htmlspecialchars($category['name'] ?? '') ?>"
                            data-category-description="<?= htmlspecialchars($category['description'] ?? '') ?>"
                            data-category-active="<?= $category['is_active'] ? 1 : 0 ?>"
                            title="Edit Category">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <form method="POST" action="admin.php" style="display: inline;" 
                          onsubmit="return confirm('Are you sure you want to delete this category?');">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                        <input type="hidden" name="action" value="delete_category">
                        <input type="hidden" name="category_id" value="<?= $category['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm" title="Delete Category">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                </div>
            </td>
        </tr>
        <?php
    }
}

/**
 * Render orders table rows
 */
function renderOrdersTable($orders) {
    if (empty($orders)) {
        echo '<tr><td colspan="5" class="text-center">No orders found</td></tr>';
        return;
    }
    
    foreach ($orders as $order) {
        // Build customer name from available fields
        $customerName = '';
        if (!empty($order['shipping_first_name']) || !empty($order['shipping_last_name'])) {
            $customerName = trim(($order['shipping_first_name'] ?? '') . ' ' . ($order['shipping_last_name'] ?? ''));
        } elseif (!empty($order['username'])) {
            $customerName = $order['username'];
        } elseif (!empty($order['email'])) {
            $customerName = $order['email'];
        } else {
            $customerName = 'Unknown Customer';
        }
        
        $totalAmount = number_format($order['total_amount'] ?? 0, 2);
        $status = $order['status'] ?? 'pending';
        // Handle empty status fallback
        if (empty($status) || trim($status) === '') {
            $status = 'pending';
        }
        $statusClass = getOrderStatusClass($status);
        $createdAt = htmlspecialchars($order['created_at'] ?? '');
        ?>
        <tr data-order-id="<?= $order['id'] ?>">
            <td class="order-customer"><?= htmlspecialchars($customerName) ?></td>
            <td class="order-total">$<?= $totalAmount ?></td>
            <td class="order-status">
                <span class="badge bg-<?= $statusClass ?>"><?= htmlspecialchars($status) ?></span>
            </td>
            <td class="order-date"><?= $createdAt ?></td>
            <td class="order-actions">
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-primary edit-order"
                            data-order-id="<?= $order['id'] ?>"
                            data-order-number="<?= htmlspecialchars($order['order_number'] ?? '') ?>"
                            data-order-status="<?= htmlspecialchars($status) ?>"
                            data-order-customer="<?= htmlspecialchars($customerName) ?>"
                            data-order-total="<?= $order['total_amount'] ?? 0 ?>"
                            title="Edit Order">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <form method="POST" action="admin.php" style="display: inline;"
                          onsubmit="return confirm('Are you sure you want to delete this order?');">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                        <input type="hidden" name="action" value="delete_order">
                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm" title="Delete Order">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                </div>
            </td>
        </tr>
        <?php
    }
}

/**
 * Render users table rows
 */
function renderUsersTable($users) {
    if (empty($users)) {
        echo '<tr><td colspan="7" class="text-center">No users found</td></tr>';
        return;
    }
    
    foreach ($users as $user) {
        $username = htmlspecialchars($user['username'] ?? $user['email'] ?? '');
        $email = htmlspecialchars($user['email'] ?? '');
        $name = htmlspecialchars($user['name'] ?? '');
        $role = $user['role'] ?? 'user';
        $roleClass = $role === 'admin' ? 'danger' : 'primary';
        $statusClass = $user['is_active'] ? 'success' : 'secondary';
        $statusText = $user['is_active'] ? 'Active' : 'Inactive';
        $createdAt = htmlspecialchars($user['created_at'] ?? '');
        ?>
        <tr data-user-id="<?= $user['id'] ?>">
            <td class="user-username"><?= $username ?></td>
            <td class="user-email"><?= $email ?></td>
            <td class="user-name"><?= $name ?></td>
            <td class="user-role">
                <span class="badge bg-<?= $roleClass ?>"><?= htmlspecialchars($role) ?></span>
            </td>
            <td class="user-status">
                <span class="badge bg-<?= $statusClass ?>"><?= $statusText ?></span>
            </td>
            <td class="user-created"><?= $createdAt ?></td>
            <td class="user-actions">
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-primary edit-user"
                            data-user-id="<?= $user['id'] ?>"
                            data-user-username="<?= htmlspecialchars($user['username'] ?? '') ?>"
                            data-user-email="<?= htmlspecialchars($user['email'] ?? '') ?>"
                            data-user-firstname="<?= htmlspecialchars($user['first_name'] ?? '') ?>"
                            data-user-lastname="<?= htmlspecialchars($user['last_name'] ?? '') ?>"
                            data-user-role="<?= htmlspecialchars($role) ?>"
                            data-user-active="<?= $user['is_active'] ? 1 : 0 ?>"
                            title="Edit User">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <form method="POST" action="admin.php" style="display: inline;"
                          onsubmit="return confirm('Are you sure you want to delete this user?');">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                        <input type="hidden" name="action" value="delete_user">
                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm" title="Delete User">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                </div>
            </td>
        </tr>
        <?php
    }
}

/**
 * Helper function to get order status CSS class
 */
function getOrderStatusClass($status) {
    switch ($status) {
        case 'delivered':
            return 'success';
        case 'pending':
            return 'warning';
        case 'processing':
            return 'info';
        case 'shipped':
            return 'primary';
        case 'cancelled':
            return 'danger';
        default:
            return 'secondary';
    }
}

/**
 * Render table with loading state
 */
function renderTableLoading($colspan = 5) {
    echo '<tr><td colspan="' . $colspan . '" class="text-center">';
    echo '<div class="spinner-border spinner-border-sm"></div> Loading...';
    echo '</td></tr>';
}

/**
 * Render empty table state
 */
function renderTableEmpty($message = 'No data found', $colspan = 5) {
    echo '<tr><td colspan="' . $colspan . '" class="text-center">' . htmlspecialchars($message) . '</td></tr>';
}
?>