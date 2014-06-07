<div class="col-md-12">
    
    <p><?php echo $this->getContent() ?></p>
    
    <div class="btn-toolbar" role="toolbar">
        <div class="btn-group">
            <?= $this->tag->linkTo(array(
                '/profile/messages/', 
                'text' => 'Просмотреть сообщения <span class="glyphicon ' . (count($messages) > 0 ? 'glyphicon-envelope' : '') . '"></span>',
                'type' => 'button',
                'class' => 'btn btn-default'
                )); ?>
        </div>
    </div>
    <br>
    
    <div class="carousel">
        <div class="page-header">
            <h4>Данные пользователя</h4>
        </div>

        <table class="table table-bordered table-condensed">
            <tr>
                <th>Логин:</th>
                <td colspan="3"><?= $user->login ?></td>
            </tr>
            <tr>
                <th>ФИО:</th>
                <td><?= $user->full_name ?></td>
                <th>Телефон:</th>
                <td><?= $user->phone ?></td>
            </tr>
            <tr>
                <td colspan="4">
                    <?= $user->location ?>
                </td>
            </tr>
        </table>
    </div>
    
    <div class="controls-row">
        <?= $this->tag->form(array('/signup/edit', 'method' => 'post')); ?>
        <?= $this->tag->hiddenField(array('uid', 'value' => $user->id)); ?>
        <?= $this->tag->submitButton(array(
        'edit_user', 
        'name' => 'edit_user',
        'value' => 'Изменить', 
        'onclick' => "return confirm('Редактировать пользователя?');",
        'class' => 'btn btn-warning')); ?>
        </form>
    </div>
    
    <div class="page-header">
        <h4>Заказы пользователя</h4>
    </div>
    <?php echo Phalcon\Tag::form(['/order/cancel', 'id' => 'order_cancel_form', 'class' => 'form-horizontal']) ?>
    <table class="table table-bordered table-condensed">
        <thead>
            <tr>
                <th>Заказ</th>
                <th>Товар</th>
                <th>Размер</th>
                <th>Количество</th>
                <th>Цена</th>
                <th>Дата заказа</th>
                <th>Статус заказа</th>
                <th>Сообщения</th>
                <th>Отмена</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order) : ?>
            <tr>
                <td><?= $order->id ?></td>
                <td><?= Product::getProductTitle($order->product_id) ?></td>
                <td><?= ProductAttribute::getAttributeTitle($order->product_attr_id) ?></td>
                <td><?= $order->product_count ?></td>
                <td><?= $order->order_summa ?></td>
                <td><?= rus_date($order->order_datetime, true) ?></td>
                <td><?= Order::getStatus($order->order_status_id) ?></td>
				<td>
					<div>
					<?= $this->tag->linkTo(array(
						'/product/msg/' . $order->id, 
						'class' => 'btn btn-sm btn-link', 
						'text' => 'Сообщить об оплате'
					)) ?>
					</div>
					<?php if (!Product::isStopped($order->product_id)) { ?>
					<div>
					<?= $this->tag->linkTo(array(
						'/product/comment_order/' . $order->id, 
						'class' => 'btn btn-sm btn-link', 
						'text' => 'Комментарий'
					)) ?>
					</div>
					<?php } ?>
                </td>
                <td><?= (!Product::isStopped($order->product_id) ? '<button class="btn btn-danger btn-sm" onclick=\'return remove_order(this);\' value="' . $order->id . '">Отменить</button>' : '') ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </form>
</div>

<script>
    <!--
    function remove_order(el) {
        if (confirm("Отменить выбранный заказ?")) { 
            $(el).attr("name", "uid"); 
            return true; 
        } return false;
    }
    -->
</script>
