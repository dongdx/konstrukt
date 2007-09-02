<?php
/**
  * The baseclass for presentation layer components.
  *
  * Components provide the capability to handle a request, often as one of
  * several components. In order to work, it must be created inside a
  * [[context|#class-k_icontext]]. Components provide the base for
  * the fully capable [[controller|#class-k_controller]], but classes
  * may also directly extend from k_Component, if their purpose is as reusable
  * presentation components, often referred to as "widgets". Examples of such
  * components are [[k_Datalist|#class-k_datalist]] and
  * [[k_FormBehaviour|#class-k_formbehaviour]].
  *
  * The [[render()|#class-k_component-method-render]] method is a simple, yet
  * powerful rendering engine. It includes a procedural PHP file, using ``include``
  * and captures the output to a string, which is returned.
  * Within the rendering template, you can use the following static functions as
  * convenient shortcuts:
  *
  * ``e(...)`` Echoes out the input. It's basically a shortcut for
  * ``echo htmlspecialchars(...)``, although it could be extended by adding callbacks
  * to [[$outputFilters|#class-k_component-property-outputFilters]]
  *
  * ``url(...)`` Shortcut for ``$this->url(...)``
  *
  * ``__(...)`` Shortcut for ``$this->__(...)``
  *
  * The implementation of these helper functions is implemented by
  * [[k_StaticAdapter|#class-k_staticadapter]]. If you need to reuse procedural
  * templates, written for use with Konstrukt, outside of Konstrukt, you can
  * provide implementations for these, by using StaticAdapter.
  */
abstract class k_Component
{
  /**
    * The parent context.
    *
    * This value is assigned in the constructor and shouldn't be changed at runtime.
    * @var k_iContext
    */
  public $context;
  /**
    * @var k_Registry
    */
  public $registry;
  /**
    * Container for URL-propagated state.
    *
    * @var k_iStateContainer
    */
  protected $state;
  /**
    * A hashmap of phrases for the built-in translation feature.
    * Used by [[__()|#class-k_component-method-__]].
    * @var hashmap
    */
  protected $i18n = Array();

  /**
    * Array of callbacks, used to format default output.
    *
    * This is used by the managed static function e()
    * Since HTML is the most common output, the default filter is htmlspecialchars.
    *
    * @var [] callback
    */
  protected $outputFilters = Array('htmlspecialchars');

  function __construct(k_iContext $context, $urlNamespace = "") {
    $this->context = $context;
    $this->registry = $this->context->getRegistry();
    $this->state = $this->context->getUrlStateContainer($urlNamespace);
    $this->initializeState();
  }

  /**
   * Initializes the state container.
   */
  protected function initializeState() {
  }

  function getRegistry() {
    return $this->registry;
  }

  function getUrlStateContainer($namespace = "") {
    return new k_UrlState($this->state, $namespace);
  }

  /**
    * Property getter.
    * Delegates to the components registry, so referring to $this->foo equals $this->registry->foo
    * @note experimental
    */
  function __get($property) {
    return $this->registry->get($property);
  }

  /**
    * Property getter.
    * @see __get
    * @note experimental
    */
  function __isset($property) {
    return isset($this->registry) && $this->registry->__isset($property);
  }

  /**
    * Returns a localized string from the stringtable
    *
    * Recurses up to the context, if the phrase is not found in the scope of this object.
    *
    * This function can be called from within a rendering template, with the shortcut function ``__``
    */
  function __($phrase) {
    if (isset($this->i18n[$phrase])) {
      return $this->i18n[$phrase];
    }
    if (method_exists($this->context, '__')) {
      return $this->context->__($phrase);
    }
    return $phrase;
  }

  /**
    * This function can be called from within a rendering template, with the shortcut function ``url``
    */
  function url($href = "", $args = Array()) {
    if (is_null($args)) {
      return $this->context->url($href, NULL);
    }
    return $this->context->url($href, $this->state->export($args));
  }

  abstract function execute();

  /**
    * Includes a file and returns the output.
    *
    * Essentially this is just a wrapper around include, which returns the
    * result rather than send it to stdout.
    * @param $file   string   Procedural PHP file to include
    * @param $model  array    assoc array of model data for the view
    * @return string
    */
  function render(/* $file, $model = Array() */) {
    if (!is_string(func_get_arg(0))) {
      throw new Exception("Wrong argument type. Expected string as first parameter");
    }
    if (func_num_args() > 1) {
      extract(func_get_arg(1));
    }
    $__template_filename__ = k_ClassLoader::SearchIncludePath(func_get_arg(0));
    if (!is_file($__template_filename__)) {
      throw new Exception("Failed opening '".func_get_arg(0)."' for inclusion. (include_path=".ini_get('include_path').")");
    }
    k_StaticAdapter::connect('e', Array($this, 'outputString'));
    k_StaticAdapter::connect('__', Array($this, '__'));
    k_StaticAdapter::connect('url', Array($this, 'url'));
    ob_start();
    try {
      include($__template_filename__);
      $buffer = ob_get_clean();
      k_StaticAdapter::disconnect('e');
      k_StaticAdapter::disconnect('__');
      k_StaticAdapter::disconnect('url');
      return $buffer;
    } catch (Exception $ex) {
      ob_end_clean();
      k_StaticAdapter::disconnect('e');
      k_StaticAdapter::disconnect('__');
      k_StaticAdapter::disconnect('url');
      throw $ex;
    }
  }

  /**
    * Outputs the input string, escaping it with the default output filters (defaults to htmlspecialchars)
    * This function can be called from within a rendering template, with the shortcut function ``e``
    */
  function outputString($str) {
    foreach ($this->outputFilters as $callback) {
      $str = call_user_func($callback, $str);
    }
    echo $str;
  }
}