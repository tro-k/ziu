<?php

class Scaffold
{

    public function init()
    {
    }

    public function main($type = FALSE)
    {
        try {
            $lib = lib('common');
            $args = func_get_args();
            $params = $lib->args($args);
            $params = $lib->config($params);
            $filepath = $lib->filepath($params);
            if ($type == 'drop') {
                $lib->rollback($filepath, TRUE);
                exit;
            }
            $args = implode('/', $args);
            $template = array(
                'model' => $lib->invoke('model/' . $args),
                'logic' => $lib->invoke('logic/' . $args),
                'view/index' => $lib->invoke('view/index/' . $args),
                'view/input' => $lib->invoke('view/input/' . $args),
                'view/confirm' => $lib->invoke('view/confirm/' . $args),
                'view/detail' => $lib->invoke('view/detail/' . $args),
                'view/complete' => $lib->invoke('view/complete/' . $args),
                'unit/index'   => $lib->invoke('unit/index/' . $args),
                'unit/default' => $lib->invoke('unit/default/' . $args),
            );
            $lib->validate($filepath);
            $lib->create($template, $filepath);
        } catch (Exception $e) {
            echo($e->getMessage() . "\n");
            if (! empty($filepath)) {
                $lib->rollback($filepath);
            }
        }
    }

}

