<?php

/* database.twig */
class __TwigTemplate_1a7f87f7c257cc7475d2052dda0cdc9b extends Twig_Template
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
\t\t
\t\t<div id=\"login-header\">
\t\t\t<h3>Database Setup</h3>
\t\t</div> <!-- #login-header -->
\t\t<div class=\"progress progress-success\">
    \t\t<div class=\"bar\" style=\"width: 60%;\"></div>
    \t</div>
    \t";
        // line 13
        if (isset($context["status"])) { $_status_ = $context["status"]; } else { $_status_ = null; }
        if (($this->getAttribute($_status_, "error") != null)) {
            // line 14
            echo "    \t<div class=\"alert alert-danger\">
    \t\t";
            // line 15
            if (isset($context["status"])) { $_status_ = $context["status"]; } else { $_status_ = null; }
            echo $this->getAttribute($_status_, "error");
            echo "
    \t</div>
    \t";
        }
        // line 18
        echo "\t\t<div id=\"login-content\" class=\"clearfix\">
\t\t\t<form method=\"post\" action=\"/admin/install/database\">
\t\t\t\t
\t\t\t\t<fieldset>
\t\t\t\t\t<div class=\"control-group\">
\t\t\t\t\t\t<label class=\"control-label\" for=\"form_database_host\">Database Host</label>
\t\t\t\t\t\t<div class=\"controls-row\">
\t\t\t\t\t\t\t<input type=\"text\" class=\"span12\" id=\"form_database_host\" name=\"database_host\" value=\"";
        // line 25
        if (isset($context["post"])) { $_post_ = $context["post"]; } else { $_post_ = null; }
        if (($this->getAttribute($_post_, "database_host") != null)) {
            if (isset($context["post"])) { $_post_ = $context["post"]; } else { $_post_ = null; }
            echo $this->getAttribute($_post_, "database_host");
        } else {
            echo "localhost";
        }
        echo "\">
\t\t\t\t\t\t</div>
\t\t\t\t\t</div>
\t\t\t\t\t<div class=\"control-group\">
\t\t\t\t\t\t<label class=\"control-label\" for=\"form_password\">Database Name</label>
\t\t\t\t\t\t<div class=\"controls-row\">
\t\t\t\t\t\t\t<input type=\"text\" class=\"span12\" id=\"form_password\" name=\"database_name\" value=\"";
        // line 31
        if (isset($context["post"])) { $_post_ = $context["post"]; } else { $_post_ = null; }
        if (($this->getAttribute($_post_, "database_name") != null)) {
            if (isset($context["post"])) { $_post_ = $context["post"]; } else { $_post_ = null; }
            echo $this->getAttribute($_post_, "database_name");
        }
        echo "\">
\t\t\t\t\t\t</div>
\t\t\t\t\t</div>
\t\t\t\t\t<div class=\"control-group\">
\t\t\t\t\t\t<label class=\"control-label\" for=\"form_database_username\">Database Username</label>
\t\t\t\t\t\t<div class=\"controls-row\">
\t\t\t\t\t\t\t<input type=\"text\" class=\"span12\" id=\"form_database_username\" name=\"database_username\" value=\"";
        // line 37
        if (isset($context["post"])) { $_post_ = $context["post"]; } else { $_post_ = null; }
        if (($this->getAttribute($_post_, "database_username") != null)) {
            if (isset($context["post"])) { $_post_ = $context["post"]; } else { $_post_ = null; }
            echo $this->getAttribute($_post_, "database_username");
        }
        echo "\">
\t\t\t\t\t\t</div>
\t\t\t\t\t</div>
\t\t\t\t\t<div class=\"control-group\">
\t\t\t\t\t\t<label class=\"control-label\" for=\"form_database_password\">Database Password</label>
\t\t\t\t\t\t<div class=\"controls-row\">
\t\t\t\t\t\t\t<input type=\"text\" class=\"span12\" id=\"form_database_password\" name=\"database_password\" value=\"";
        // line 43
        if (isset($context["post"])) { $_post_ = $context["post"]; } else { $_post_ = null; }
        if (($this->getAttribute($_post_, "database_password") != null)) {
            if (isset($context["post"])) { $_post_ = $context["post"]; } else { $_post_ = null; }
            echo $this->getAttribute($_post_, "database_password");
        }
        echo "\">
\t\t\t\t\t\t</div>
\t\t\t\t\t</div>
\t\t\t\t\t
\t\t\t\t</fieldset>
\t\t\t\t
\t\t\t\t<div class=\"pull-right\">
\t\t\t\t\t<button type=\"submit\" class=\"btn-primary btn-large\">Create Database</button>
\t\t\t\t</div>
\t\t\t\t
\t\t\t</form>
\t</div> <!-- #login-container -->
\t
";
    }

    public function getTemplateName()
    {
        return "database.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  103 => 43,  90 => 37,  77 => 31,  62 => 25,  53 => 18,  46 => 15,  43 => 14,  40 => 13,  29 => 4,  26 => 3,);
    }
}
