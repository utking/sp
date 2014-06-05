<div class="col-md-12">
    
    <p><?php echo $this->getContent() ?></p>
    
    <?php echo Phalcon\Tag::form(['/profile/update_messages', 'id' => 'messages_form', 'class' => 'form-horizontal']) ?>
    
    <div class="btn-toolbar" role="toolbar">
        <div class="btn-group">
            <?= $this->tag->linkTo(array(
                '/profile/', 
                'text' => 'Назад к профилю',
                'type' => 'button',
                'class' => 'btn btn-default'
                )); ?>
        </div>
    </div>
    <br>
    
    <div class="carousel">
        <div class="page-header">
            <h4>Сообщения</h4>
        </div>
        <div class="btn-toolbar form-group" role="toolbar">
            <div class="btn-group">
                <?php echo Phalcon\Tag::submitButton(["Показать все", 'name' => 'all_messages', 'class' => 'btn btn-default']) ?>
                <?php echo Phalcon\Tag::submitButton(["Показать не прочтенные", 'name' => 'unread_messages', 'class' => 'btn btn-default']) ?>
                <?php echo Phalcon\Tag::submitButton(["Удалить", 'name' => 'remove_messages', 'class' => 'btn btn-default']) ?>
                <?php echo Phalcon\Tag::submitButton(["Пометить прочтенными", 'name' => 'mark_messages_read', 'class' => 'btn btn-default']) ?>
            </div>
        </div>
        <table class="table table-bordered table-condensed">
            <?php foreach ($messages as $msg) : ?>
            <tr class='<?= $msg->is_new ? 'new_message' : ''?>'>
                <td style='width: 10px;'><?php echo Phalcon\Tag::checkField(["message_ids[]", 'value' => $msg->id]) ?></td>
                <td>
                    <a href='#' class="msg_subj" onclick="toggle_msg_body(this); return false;"><?= $msg->msg_subject ?></a>
                    <div class='msg_body'><pre><?= $msg->msg ?></pre></div>
                </td>
                <td style='width: 150px;'><?= rus_date($msg->item_datetime, true) ?></td>
                <td style='width: 150px; <?= $msg->from_user_id < 1 ? 'font-weight: bold' : '' ?>'><?= User::getLogin($msg->from_user_id) ?></td>
                <td style='width: 80px;'>
                    <?= $this->tag->linkTo(array(
                        '/profile/reply_message/' . $msg->id, 
                        'text' => 'Ответить',
                        'type' => 'button',
                        'class' => 'btn btn-link btn-sm'
                        )); ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    
    </form>
    
</div>

<script>
    <!--
    function toggle_msg_body(el) {
        var cell = $(el).parent('td');
        $(cell).find('.msg_body').toggle();
    }
    -->
</script>