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
        </table>
    </div>
    

</div>