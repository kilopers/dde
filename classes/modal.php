<?php


namespace local_dde;

class modal
{
    private $tag;
    private $content;
    private $attr = [];
    private $body = '';
    private $modalid = '';


    function __construct($tag, $content, $attr = [])
    {
        $this->tag = $tag;
        $this->content = $content;
        $this->attr = $attr;
        $this->modalid = "dde-modal".rand(0,1e9);
        $this->attr['modal-id'] = $this->modalid;
    }

    function setAfterOpen($amd, $func)
    {
        $this->attr['modal-afteropen-amd'] = $amd;
        $this->attr['modal-afteropen-func'] = $func;
    }
    function setAfterAjax($amd, $func)
    {
        $this->attr['modal-afterajax-amd'] = $amd;
        $this->attr['modal-afterajax-func'] = $func;
    }
    function setAfterClose($amd, $func)
    {
        $this->attr['modal-afterclose-amd'] = $amd;
        $this->attr['modal-afterclose-func'] = $func;
    }
    function setTitle($title)
    {
        $this->attr['modal-title'] = $title;
    }
    function setBody($body)
    {
        $this->attr['modal-body'] = $body;
    }

    function setBodyAjax($ajax, $amd = '', $func = '')
    {
        $this->attr['modal-bodyajax'] = $ajax;
        if($amd && $func)
            $this->setAfterAjax($amd, $func);
    }

    function render()
    {
        global $PAGE;
        $html = \html_writer::tag($this->tag, $this->content, $this->attr);
        $PAGE->requires->js_call_amd('local_dde/alert', 'init', ['[modal-id="'.$this->modalid.'"]', $this->modalid]);
        return $html;
    }
}