<p><?php echo $this->getContent() ?></p>
<div class="btn-toolbar" role="toolbar">
    <div class="btn-group">
        <?= $this->tag->linkTo(array(
            '/categories/index/', 
            'text' => 'Назад к списку закупок',
            'type' => 'button',
            'class' => 'btn btn-default'
            )); ?>
        <?php if ($category->parent_category_id) { ?>
        <?= $this->tag->linkTo(array(
            '/categories/view/' . $category->parent_category_id, 
            'text' => 'К родительской категории',
            'type' => 'button',
            'class' => 'btn btn-default'
            )); ?>
        <?php } ?>
    </div>
</div>

<?php if (count($category_child_cats)) { ?>

<div>
    <br>
    <div class="title">Закупки в категории</div><br>
    <table class="table table-bordered table-condensed cat_table">
        <tr>
            <td class="cat_title">Название и фото</td>
            <td class="cat_desc">Описание</td>
            <td class="cat_products_count">Товаров в закупке</td>
            <td class="cat_products_count">Стоп</td>
        </tr>
    <?php foreach ($category_child_cats as $category) { ?>
        <tr>
            <td class="cat_title">
                <div class="title"><?= $category->title ?></div>
                <?= $this->tag->linkTo(array(
                    '/categories/view/' . $category->id, 
                    'text' => 
                    (strlen($category->img) > 0 ? ('<img class="cat_img" src="data:image/jpeg;charset=utf-8;base64,' . $category->img . '">') : ('<img class="cat_img" src="/img/noimage.jpg">')))); ?>
            </td>
            <td class="cat_desc"><?= $category->desc ?></td>
            <td class="cat_products_count"><?= count(Product::find(array('conditions' => 'category_id = ?1', 'bind' => array( 1 => $category->id)))) ?></td>
            <?php $stop_datetime = new DateTime($category->stop_datetime); ?>
            <td class="cat_products_count"><span class="<?= (!Categories::isStopped($category->id) ? '' : ' errorMessage ')  ?>"><?= $stop_datetime->format('d.m.Y H:i:s') ?></span></td>
        </tr>
    <?php } ?>
    </table>
</div>

<?php } else { ?>

<div>
    <br>
    <div class="">
        <div class="title">Условия закупки &laquo;<?= $category->title ?>&raquo;</div>
        <div class="alert alert-info"><?= $category->rules ?></div>
    </div>
    
    <div class="title">Товары в закупке</div><br>
    <table class="table table-bordered table-condensed product_table">
    <?php foreach ($this->view->products as $product) { ?>
        <tr>
            <td class="product_title">
                <div class="title"><?= $product->title ?></div>
                <?php echo $this->tag->linkTo(array(
                    '/product/view/' . $product->id, 
                    'text' => 
                    (strlen(ProductImage::getProductImage($product->id)) > 0 ? ('<img class="cat_img" src="data:image/jpeg;charset=utf-8;base64,' . ProductImage::getProductImage($product->id) . '">') : ('<img class="cat_img" src="/img/noimage.jpg">'))
                    )); ?>
            </td>
            <td class="cat_desc"><?= $product->description ?></td>
            <td class="product_price price"><?= $product->price ?></td>
        </tr>
    <?php } ?>
    </table>
</div>

<?php echo Phalcon\Tag::form(['/categories/new_message', 'id' => 'forum_new_message_form', 'class' => 'form-horizontal']) ?>
<div class="forum_block" id="new_msg">
    <h3 class="help-block">Обсуждение закупки</h3>
    <div id="forum_new_msg">
        <table class="table">
            <tr>
                <td style='width: 70px;'><label for="forum_new_msg_text">Сообщение:</label></td>
                <td>
                    <?php echo Phalcon\Tag::textarea(["forum_new_msg_text", 'class' => 'form-control']) ?>
                </td>
            </tr>
            <tr>
                <td></td>
                <td>
                    <?= Recaptcha::get('6LdFpfQSAAAAACrEsxtX55STBL26Ax8s2Z4N9YFd'); ?>
                </td>
            </tr>
            <tr>
                <td></td>
                <td>
                    <?php echo Phalcon\Tag::submitButton(["Отправить", 'name' => 'forum_send_new_msg', 'class' => 'btn btn-default', 'onclick' => 'return check_forum_msg();']) ?>
                    <?php echo Phalcon\Tag::hiddenField(["category_id", 'value' => $category->id]) ?>
                </td>
            </tr>
        </table>
    </div>
    
    <table class="table table-condensed table-bordered forum_msgs_table">
        <?php foreach ($forum_msgs as $cur_msg) : ?>
        <tr>
            <td style="width: 40px;">
                 <?php echo $this->tag->linkTo(array(
                    '#' . $cur_msg->id,
                     'text' => '#' . $cur_msg->id,
                     'id' => $cur_msg->id
                    )); ?>
            </td>
            <td class="forum_msg_info">
                <span class="forum_info_user"><?= User::getLogin($cur_msg->user_id) ?></span>
                <span class="forum_info_date"><?= rus_date($cur_msg->item_datetime, true) ?></span>
            </td>
            <td class="forum_msg_text">
                <span class=""><?= $cur_msg->msg ?></span>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
</form>

<?php } ?>

<script>
    <!--
    function check_forum_msg() {
        if ($.trim($('#forum_new_msg_text').val()) !== '') {
            return true;
        } else {
            $('#forum_new_msg_text').select();
            $('#forum_new_msg_text').focus();
            return false;
        }
    }
    -->
</script>
