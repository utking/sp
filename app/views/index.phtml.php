<!DOCTYPE html>

<html>
    <head>
        <meta charset="utf-8"/>
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <link rel="stylesheet" href="<?= $this->di->get('config')->application->baseUri ?>/css/datepicker_style.css"/>
        <link rel="stylesheet" href="<?= $this->di->get('config')->application->baseUri ?>/css/bootstrap-responsive.css" />
        <link rel="stylesheet" href="<?= $this->di->get('config')->application->baseUri ?>/css/bootstrap.css" />
        <link href="<?= $this->di->get('config')->application->baseUri ?>/css/jquery-ui.css" rel="stylesheet" />
        <link rel="stylesheet" href="<?= $this->di->get('config')->application->baseUri ?>/css/style.css" />
        <script src="<?= $this->di->get('config')->application->baseUri ?>/js/jquery-1.8.2.js"></script>
        <script src="<?= $this->di->get('config')->application->baseUri ?>/js/jquery-ui.js"></script>
        <script src="<?= $this->di->get('config')->application->baseUri ?>/js/jq.numeric.js"></script>
        <script src="<?= $this->di->get('config')->application->baseUri ?>/js/bootstrap.js"></script>
        <script src="<?= $this->di->get('config')->application->baseUri ?>/js/jquery.elevatezoom.js"></script>
        
        <script>
            $(function() {
                $("#datepicker").datepicker();
            });
        </script>
        <?php echo $this->tag->getTitle() ?>
    </head>

    <body>
        <div id="wrap">
            <div role="navigation" class="navbar-inverse">
                <div class="container">
                    <div class="navbar-header">
                        <button data-target=".navbar-collapse" data-toggle="collapse" class="navbar-toggle" type="button">
                            <span class="sr-only">Toggle navigation</span>
                            <span class="icon-bar"></span>
                            <span class="icon-bar"></span>
                            <span class="icon-bar"></span>
                        </button>
                        <a href="#" class="navbar-brand">SP - совместные покупки</a>
                    </div>
                    <div class="collapse navbar-collapse">
                        <?php echo $this->elements->getMenu() ?>
                    </div>
                </div>
            </div>
            <div class="container body_div">
                <div class="row">
                    <div class="col-md-12">
                        <p><?php $this->flashSession->output() ?></p>
                        <?php echo $this->getContent() ?>
                    </div>
                </div>
            </div>
        </div>
        <div id="footer">
            <div class="container">
                <p class="text-muted">SP - совместные покупки, 2014 &copy;</p>
            </div>
        </div>
    </body>
</html>
