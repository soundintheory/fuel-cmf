<?php

/* setup.twig */
class __TwigTemplate_90577cecb75a1a9fda835a3f3f7e171b extends Twig_Template
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
\t\t\t<h3>Project Setup</h3>
\t\t</div> <!-- #login-header -->
\t\t    <div class=\"progress\">
    ";
        // line 11
        if (isset($context["status"])) { $_status_ = $context["status"]; } else { $_status_ = null; }
        if ($_status_) {
            echo " 
\t<div class=\"bar bar-success\" style=\"width: 40%;\"></div>
\t";
        } else {
            // line 14
            echo "\t<div class=\"bar bar-success\" style=\"width: 20%;\"></div>
    ";
        }
        // line 16
        echo "    </div>
\t\t<div id=\"login-content\" class=\"clearfix\">

\t\t\t";
        // line 19
        if (isset($context["status"])) { $_status_ = $context["status"]; } else { $_status_ = null; }
        if ($_status_) {
            echo " 
\t\t\t<p>Excellent! All files copies successfully. Next step - database.</p>
\t\t\t<p><a class=\"btn btn-success span6 offset3\" href=\"/admin/install/database\">Database Setup</a></p>
\t\t\t";
        } else {
            // line 23
            echo "\t\t\t<form method=\"post\" action=\"/admin/install/setup/go\">
\t\t\t\t<p><em>Enter project info and submit to copy the files and set the project up.</em></p>
\t\t\t\t<fieldset>
\t\t\t\t\t<div class=\"control-group\">
\t\t\t\t\t\t<label class=\"control-label\" for=\"form_project_name\">Project Name</label>
\t\t\t\t\t\t<div class=\"controls-row\">
\t\t\t\t\t\t\t<input type=\"text\" class=\"span12\" id=\"form_project_name\" name=\"project_name\" value=\"\">
\t\t\t\t\t\t</div>
\t\t\t\t\t</div>
\t\t\t\t\t<div class=\"control-group\">
\t\t\t\t\t\t<label class=\"control-label\" for=\"form_admin_title\">Admin Title</label>
\t\t\t\t\t\t<div class=\"controls-row\">
\t\t\t\t\t\t\t<input type=\"text\" class=\"span12\" id=\"form_admin_title\" name=\"admin_title\" value=\"\">
\t\t\t\t\t\t</div>
\t\t\t\t\t</div>
\t\t\t\t</fieldset>
\t\t\t\t
\t\t\t\t<div class=\"pull-right\">
\t\t\t\t\t<button type=\"submit\" class=\"btn-primary btn-large\">Setup Project</button>
\t\t\t\t</div>
\t\t\t\t
\t\t\t</form>
\t\t\t";
        }
        // line 45
        echo " 
\t</div> <!-- #login-container -->
\t
";
    }

    public function getTemplateName()
    {
        return "setup.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  87 => 45,  62 => 23,  54 => 19,  49 => 16,  45 => 14,  38 => 11,  29 => 4,  26 => 3,);
    }
}
