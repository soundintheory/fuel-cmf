<?php

/* index.twig */
class __TwigTemplate_f75abf844f4d7c67eaf7c6ea44f6fffa extends Twig_Template
{
    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        $this->parent = $this->env->loadTemplate("admin/shared/base.twig");

        $this->blocks = array(
            'content' => array($this, 'block_content'),
        );
    }

    protected function doGetParent(array $context)
    {
        return "admin/shared/base.twig";
    }

    protected function doDisplay(array $context, array $blocks = array())
    {
        $this->parent->display($context, array_merge($this->blocks, $blocks));
    }

    // line 3
    public function block_content($context, array $blocks = array())
    {
        // line 4
        echo "\t 
\t<div id=\"login-container\" class=\"row-fluid\">
<!-- <ul class=\"nav nav-tabs\">
<li class=\"tab active\"><a href=\"\">Start</a></li>
<li class=\"tab\"><a href=\"\">Copy files</a></li>
<li class=\"tab\"><a href=\"\">Database</a></li>
<li class=\"tab\"><a href=\"\">Migration</a></li>
<li class=\"tab\"><a href=\"\">Super User</a></li>
</ul> -->
 <!--    <ul class=\"nav nav-pills\">
    \t<li class=\"active\"><a href=\"\">Start</a></li>
    \t<li class=\"\"><a href=\"\">Files</a></li>
    \t<li class=\"\"><a href=\"\">Database</a></li>
    \t<li class=\"disabled\"><a href=\"\">Migration</a></li>
    \t<li class=\"disabled\"><a href=\"\">Super User</a> </li>
    </ul>-->
\t\t<div id=\"login-header\"> 
\t\t\t<h3>Welcome to the CMF Installer</h3>
\t\t</div> <!-- #login-header -->
\t\t
\t\t<div id=\"login-content\" class=\"clearfix\">

\t\t\t<p><a class=\"btn btn-primary span6 offset3\" href=\"/admin/install/setup\">Begin Installation</a></p>
\t\t\t<!-- <form method=\"post\" action=\"/admin/login\">
\t\t\t\t
\t\t\t\t<fieldset>
\t\t\t\t\t<div class=\"control-group\">
\t\t\t\t\t\t<label class=\"control-label\" for=\"form_username\">Username</label>
\t\t\t\t\t\t<div class=\"controls-row\">
\t\t\t\t\t\t\t<input type=\"text\" class=\"span12\" id=\"form_username\" name=\"username\">
\t\t\t\t\t\t</div>
\t\t\t\t\t</div>
\t\t\t\t\t<div class=\"control-group\">
\t\t\t\t\t\t<label class=\"control-label\" for=\"form_password\">Password</label>
\t\t\t\t\t\t<div class=\"controls-row\">
\t\t\t\t\t\t\t<input type=\"password\" class=\"span12\" id=\"form_password\" name=\"password\">
\t\t\t\t\t\t</div>
\t\t\t\t\t</div>
\t\t\t\t</fieldset>
\t\t\t\t
\t\t\t\t<div class=\"pull-right\">
\t\t\t\t\t<button type=\"submit\" class=\"btn-primary btn-large\">Login</button>
\t\t\t\t</div>
\t\t\t\t
\t\t\t</form> -->
\t\t\t
\t\t</div> <!-- #login-content -->
\t\t
\t\t<!-- div id=\"login-extra\">
\t\t\t<p><a href=\"#\">Forgotten your password?</a></p>
\t\t</div --> <!-- #login-extra -->
\t\t
\t</div> <!-- #login-container -->
\t
";
    }

    public function getTemplateName()
    {
        return "index.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  29 => 4,  26 => 3,);
    }
}
