<?php

/* admin/shared/base.twig */
class __TwigTemplate_e6ba79ea861b04c83656fe9a1d4fe61a extends Twig_Template
{
    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = array(
            'html_title' => array($this, 'block_html_title'),
            'admin_css' => array($this, 'block_admin_css'),
            'admin_js' => array($this, 'block_admin_js'),
            'body_class' => array($this, 'block_body_class'),
            'content' => array($this, 'block_content'),
        );
    }

    protected function doDisplay(array $context, array $blocks = array())
    {
        // line 1
        echo "<!DOCTYPE html>
<html lang=\"en\">
<head>

\t<meta charset=\"utf-8\">
\t<title>";
        // line 6
        $this->displayBlock('html_title', $context, $blocks);
        echo "</title>
\t<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
\t<meta name=\"description\" content=\"Login\">
\t<meta name=\"author\" content=\"Sound in Theory\">

    <!--[if lt IE 9]>
        <script src=\"/admin/assets/js/html5.js\"></script>
\t<![endif]-->
\t
\t<link href=\"/admin/assets/css/bootstrap.min.css\" rel=\"stylesheet\" type=\"text/css\">
\t<link href=\"/admin/assets/css/bootstrap-responsive.min.css\" rel=\"stylesheet\" type=\"text/css\">
\t<link href=\"/admin/assets/css/ui-theme/jquery-ui-1.8.16.custom.css\" rel=\"stylesheet\" type=\"text/css\">
\t<link href=\"/admin/assets/css/enhanced.css\" rel=\"stylesheet\" type=\"text/css\">
\t<link href=\"/admin/assets/less/core.less\" rel=\"stylesheet\" type=\"text/css\">
\t
\t<!--[if lt IE 8]>
        <link href=\"/admin/assets/css/font-awesome-ie7.css\" rel=\"stylesheet\" type=\"text/css\">
\t<![endif]-->
\t\t
\t";
        // line 25
        $this->displayBlock('admin_css', $context, $blocks);
        // line 26
        echo "\t
\t<!--[if lt IE 9]>
        <link href=\"/admin/assets/css/ui-theme/jquery.ui.1.8.16.ie.css\" rel=\"stylesheet\" type=\"text/css\">
\t<![endif]-->
\t
    <!-- Le fav and touch icons -->
    <!-- link rel=\"shortcut icon\" href=\"images/favicon.ico\">
    <link rel=\"apple-touch-icon\" href=\"images/apple-touch-icon.png\">
    <link rel=\"apple-touch-icon\" sizes=\"72x72\" href=\"images/apple-touch-icon-72x72.png\">
    <link rel=\"apple-touch-icon\" sizes=\"114x114\" href=\"images/apple-touch-icon-114x114.png\" -->
    
    <script src=\"/admin/assets/js/jquery-1.7.2.min.js\"></script>
\t<script src=\"/admin/assets/js/php.min.js\"></script>
\t<script src=\"/admin/assets/js/jquery.cookie.js\"></script>
\t<script src=\"/admin/assets/js/tree.jquery.js\"></script>
\t<script src=\"/admin/assets/js/jquery-ui-1.8.22.custom.min.js\"></script>

\t<!-- Nice UI Addons -->
\t<script type=\"text/javascript\" src=\"/admin/assets/js/jquery-ui-timepicker-addon.js\"></script>
\t<script type=\"text/javascript\" src=\"/admin/assets/js/jquery-ui-sliderAccess.js\"></script>
\t<script src=\"/admin/assets/js/enhance.min.js\" type=\"text/javascript\"></script>
    <script src=\"/admin/assets/js/fileinput.jquery.js\" type=\"text/javascript\"></script>
    <script src=\"/admin/assets/js/bootstrap.min.js\"></script>
    
\t";
        // line 50
        $this->displayBlock('admin_js', $context, $blocks);
        // line 55
        echo "\t
\t<script src=\"/admin/assets/js/core.js\"></script>
\t
\t
\t
</head>
<body class=\"";
        // line 61
        $this->displayBlock('body_class', $context, $blocks);
        echo "\">
\t
\t";
        // line 63
        $this->displayBlock('content', $context, $blocks);
        // line 65
        echo "\t
</body>
</html>
";
    }

    // line 6
    public function block_html_title($context, array $blocks = array())
    {
        echo "Website Administration";
    }

    // line 25
    public function block_admin_css($context, array $blocks = array())
    {
    }

    // line 50
    public function block_admin_js($context, array $blocks = array())
    {
        // line 51
        echo "\t<script type=\"text/javascript\">
\t    var data = ";
        // line 52
        if (isset($context["js_data"])) { $_js_data_ = $context["js_data"]; } else { $_js_data_ = null; }
        echo $_js_data_;
        echo ";
\t</script>
\t";
    }

    // line 61
    public function block_body_class($context, array $blocks = array())
    {
    }

    // line 63
    public function block_content($context, array $blocks = array())
    {
        // line 64
        echo "\t";
    }

    public function getTemplateName()
    {
        return "admin/shared/base.twig";
    }

    public function getDebugInfo()
    {
        return array (  136 => 64,  133 => 63,  128 => 61,  120 => 52,  117 => 51,  114 => 50,  109 => 25,  103 => 6,  96 => 65,  94 => 63,  89 => 61,  81 => 55,  79 => 50,  53 => 26,  51 => 25,  22 => 1,  87 => 45,  62 => 23,  54 => 19,  49 => 16,  45 => 14,  38 => 11,  29 => 6,  26 => 3,);
    }
}
