<p><?php echo $this->getContent() ?></p>

<div class="title"><?= Product::getProductTitle($order->product_id) ?></div>
<?php echo Phalcon\Tag::form(['/order/update', 'id' => 'order_form', 'class' => 'form-horizontal']) ?>
<table class="table product_details_table">
    <tbody>
        <tr>
            <td class="product_title">
                <?= (strlen(ProductImage::getProductImage($order->product_id)) > 0 ? ('<img class="product_img" src="data:image/jpeg;charset=utf-8;base64,' . ProductImage::getProductImage($order->product_id) . '">') : ('<img class="product_img" src="/img/noimage.jpg">')) ?>
            </td>
            <td class="product_title"><?= Product::getProductTitle($order->product_id) ?></td>
            <td class="product_title"><?= ProductAttribute::getAttributeTitle($order->product_attr_id) ?></td>
            <td class="product_title price"><?= $order->order_summa ?></td>
            <td class="product_title"><?= $order->product_count ?> шт</td>
            <td class="product_title">пользователь: <br><?= User::getLogin($order->user_id) ?></td>
        </tr>
    </tbody>
</table>
<div class="">
    <table class="table_order_status">
        <tr>
            <td>
                <label for='order_status'>Статус заказа: </label>
            </td>
            <td>
                <select id='order_status' name="order_status" class="">
                    <?php foreach ($order_statuses as $status) { ?>
                    <option <?= ($status->id == $order->order_status_id ? ' SELECTED ' : '') ?> value="<?= $status->id ?>"><?= $status->status_title ?></option>
                    <?php } ?>
                </select>
            </td>
    </table>
</div>

<div id='msg_vid' class="form-group"></div>
<div class="form-group">
    <button class="btn btn-success" id='send_message' name='send_message' onclick="return send_message_to_user();">Написать пользователю</button>
</div>

<div class="form-group">
    <button class="btn btn-primary" id='save_order' name='save_order'>Сохранить</button>
    <button class="btn" id='remove_order' name='remove_order' onclick="return confirm('Удалить заказ?')">Удалить</button>
    <?php echo Phalcon\Tag::hiddenField(["order_id", 'value' => $order->id]) ?>
</div>

<?php if (count($order_messages)) : ?>
<br>
<div class="">
    <div class="title">Сообщения по заказу</div>
    <table class="table table-bordered table-condensed table-striped table-hover table_order_messages">
        <tr>
            <td>Дата</td>
            <td>Сообщение</td>
        </tr>
        <?php foreach ($order_messages as $msg) { ?>
        <tr>
            <td><?= rus_date($msg->item_datetime, true) ?></td>
            <td><?= $msg->msg ?></td>
        </tr>
        <?php } ?>
    </table>
</div>
<?php endif; ?>

</form>

<script>
    <!--
    function send_message_to_user() {
        var message_box = $('#msg_box');
        if (message_box.length) {
            if ($.trim($(message_box).val()) === '') {
                $(message_box).select();
                $(message_box).focus();
            } else {
                return true;
            }
        } else {
            $('#msg_vid').append('<label for="msg_box">Сообщение:</label><textarea id="msg_box" name="msg_box" placeholder="Введите сообщение" class="form-control"></textarea>');
        }
        return false;
    }
    -->
</script>