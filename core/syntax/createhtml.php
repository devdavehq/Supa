<?php

class Createhtml {

    private $append = null;
    private $appendhtlm = null;

    private $attributes;
    public function __construct() 
    {
       
    }

    public function html()
    {
        echo <<<HTML
                <!DOCTYPE html>
                <html>
                    {$this->append}
                    
                </html>
        HTML;

        return $this;
    }

    public function head($title)
    {
        $this->append = <<<HTML
                <head>
                    <title>{$title}</title>
                </head>
            HTML;
        echo $this->append;
        return $this;
    }

    public function meta($meta)
    {
       
        foreach($meta as $attr):

            $this->append += <<<HTML
               <meta {$meta} >
            HTML;
           
        endforeach;
        
        echo $this->append;
        return $this;
    }


    public function body($attr)
    {
       $this->attributes = implode(',', $attr);
       $this->append = <<<HTML
               <body {$this->attributes}>
                    {$this->append}
               </body>
            HTML;
            echo $this->append;
        return $this;
    }

    public function form($attr)
    {
       $this->attributes = implode(',', $attr);
       $this->append = <<<HTML
               <form {$this->attributes}>
                    {$this->append}
               </form>
            HTML;
            echo $this->append;
        return $this;
    }

    public function customhtml($html)
    {
            echo $html;
            return $this;
    }

    public function tag($tag , $vals = null)
    {
        if ($vals === null) {
            $this->append += "<{$tag}>";
        }else{

            foreach($vals as $val):
               
                $this->append += "<{$tag}>{$val}</{$tag}>";
            endforeach;
        }
        echo $this->append;
        return $this;
    }

    public function foreach($data)
    {
       $arr = '';
        foreach($data as $attr):

            $arr += $attr . ',';
           
        endforeach;
        
        return $arr;
         
    }
}


