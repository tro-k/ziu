<?php
/* vim:se et ts=4 sw=4 sts=4: */
/**
 * #Table# Logic.
 * 
 * LICENSE: MIT License.
 * 
 * @copyright 2012 Topazos, Inc.
 * @since File available since Release 1.0.0
 */

class #Table#
{

    /**
     * Init controller
     */
    public function init()
    {
        $this->dao = model('#table#');
        lib('form')->config(array('except_values' => 'action'));
    }

    /**
     * index
     */
    public function index($page_id = 1)
    {
        $req = $this->unit->request();
        render_set(array('f' => lib('form')->values($req)), FALSE);
        if ($req['action'] == 'reset') {
            redirect_to('#table#');
        }
        try {
            $error = '';
            $valid = array();
            $pn = paginate($page_id);
            $list = $this->dao->getAll($pn->limit(), $pn->offset(), $req);
            render_set(array('list' => $list));
            render_set(array('page' => $pn->config(array(
                'total_row' => $this->dao->foundRows(),
            ))->execute()->links()), FALSE);
        } catch (Exception $e) {
            render_set(array('error' => $e->getMessage()));
        }
        render_name('#table#/#func_prefix#index');
    }

    /**
     * add
     */
    public function add($mode = FALSE)
    {
        $module = module_uri(TRUE);
        $req = $this->unit->request();
        render_set(array(
                        'module' => $module,
                        'f' => lib('form')->values($req)
                    ), FALSE);
        switch ($mode) {
            case 'back' :
                render_name('#table#/#func_prefix#input');
                break;
            case 'confirm' :
                if ($req['action'] == 'back') {
                    redirect_to(
                        empty($req['refer']) ? '#table#' : rawurldecode($req['refer'])
                    );
                }
                try {
                    $error = '';
                    $valid = array();
                    if (! $this->dao->validate($req, $valid, $error)) {
                        throw new Exception($error);
                    }
                    render_name('#table#/#func_prefix#confirm');
                } catch (Exception $e) {
                    render_set(array('error' => $e->getMessage()));
                    render_name('#table#/#func_prefix#input');
                }
                break;
            case 'execute' :
                if ($req['action'] == 'back') {
                    render_name('#table#/#func_prefix#input');
                } else {
                    try {
                        $error = '';
                        $valid = array();
                        if (! $this->dao->validate($req, $valid, $error)) {
                            throw new Exception($error);
                        }
                        $this->dao->insert($valid);
                        redirect_to("$module/complete?refer={$req['refer']}");
                    } catch (Exception $e) {
                        render_set(array('error' => $e->getMessage()));
                        render_name('#table#/#func_prefix#input');
                    }
                }
                break;
            case 'complete' :
                $refer = lib('request')->get('refer', '#table#');
                render_name('#table#/#func_prefix#complete');
                render_set(array('refer' => rawurldecode($refer)));
                break;
            default :
                $req['refer'] = rawurlencode(getenv('HTTP_REFERER'));
                lib('form')->values($req);
                render_name('#table#/#func_prefix#input');
        }
    }

    /**
     * edit
     */
    public function edit($id = FALSE, $mode = FALSE)
    {
        $module = module_uri(TRUE) . '/' . $id;
        $req = lib('request')->post();
        render_set(array(
                        'module' => $module,
                        'f' => lib('form')->values($req)
                    ), FALSE);
        switch ($mode) {
            case 'back' :
                render_name('#table#/#func_prefix#input');
                break;
            case 'confirm' :
                if ($req['action'] == 'back') {
                    redirect_to(
                        empty($req['refer']) ? '#table#' : rawurldecode($req['refer'])
                    );
                }
                try {
                    $error = '';
                    $valid = array();
                    if ($id != $req['#table_id#']) {
                        throw new Exception('不正なアクセスです。');
                    } elseif (! $this->dao->validate($req, $valid, $error, $req['#table_id#'])) {
                        throw new Exception($error);
                    }
                    render_name('#table#/#func_prefix#confirm');
                } catch (Exception $e) {
                    render_set(array('error' => $e->getMessage()));
                    render_name('#table#/#func_prefix#input');
                }
                break;
            case 'execute' :
                if ($req['action'] == 'back') {
                    render_name('#table#/#func_prefix#input');
                } else {
                    try {
                        $error = '';
                        $valid = array();
                        if ($id != $req['#table_id#']) {
                            throw new Exception('不正なアクセスです。');
                        } elseif (! $this->dao->validate($req, $valid, $error, $req['#table_id#'])) {
                            throw new Exception($error);
                        }
                        $this->dao->update($valid, $req['#table_id#']);
                        redirect_to("$module/complete?refer={$req['refer']}");
                    } catch (Exception $e) {
                        render_set(array('error' => $e->getMessage()));
                        render_name('#table#/#func_prefix#input');
                    }
                }
                break;
            case 'complete' :
                $refer = lib('request')->get('refer', '#table#');
                render_name('#table#/#func_prefix#complete');
                render_set(array('refer' => rawurldecode($refer)));
                break;
            default :
                try {
                    $data = $this->dao->getRow($id);
                    $data['refer'] = rawurlencode(getenv('HTTP_REFERER'));
                    lib('form')->values($data);
                } catch (Exception $e) {
                    render_set(array('error' => $e->getMessage()));
                }
                render_name('#table#/#func_prefix#input');
        }
    }

    /**
     * detail
     */
    public function detail($id)
    {
        try {
            $data = $this->dao->getRow((int)$id);
            $data['refer'] = getenv('HTTP_REFERER');
            render_set(array(
                'f' => lib('form')->values($data)
            ), FALSE);
            render_name('#table#/#func_prefix#detail');
        } catch (Exception $e) {
            render_set(array('error' => $e->getMessage()));
            $this->index();
        }
    }

    /**
     * delete
     */
    public function delete($id)
    {
        try {
            $this->dao->delete((int)$id);
            redirect_to(uri('#table#'));
        } catch (Exception $e) {
            render_set(array('error' => $e->getMessage()));
            $this->index();
        }
    }

}

