<p><?php echo $this->getContent() ?></p>
<div>
    <div class="title">Список заказов</div><br>
    <table class="table table-bordered table-condensed product_table">
        <thead>
            <tr class="warning">
                <th>Заказ</th>
                <th>Товар</th>
                <th>Размер</th>
                <th>Количество</th>
                <th>Статус</th>
                <th>Пользователь</th>
                <th>Дата заказа</th>
                <th>Сообщения</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($orders as $order) { ?>
            <tr>
                <td><?php echo $this->tag->linkTo(array(
                    '/order/view/' . $order->id, 
                    'class' => 'btn',
                    'text' => $order->id)); ?></td>
                <td><?= Product::getProductTitle($order->product_id) ?></td>
                <td><?= ProductAttribute::getAttributeTitle($order->product_attr_id) ?></td>
                <td><?= $order->product_count ?></td>
                <td><?= Order::getStatus($order->order_status_id) ?></td>
                <td><?= User::getLogin($order->user_id) ?></td>
                <td><?= rus_date($order->order_datetime, true) ?></td>
                <?php $messages = Order::getOrderMessages($order->id); ?>
                <td class="product_price"><span class="glyphicon <?= (count($messages) > 0 ? 'glyphicon-envelope' : '') ?>"></span><span class="glyphicon <?= (strlen($order->info) > 0 ? 'glyphicon-comment' : '') ?>"></span></td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>
