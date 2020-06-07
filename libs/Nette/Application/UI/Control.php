<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette\Application\UI;

use Nette;


/**
 * Control is renderable Presenter component.
 *
 * @author     David Grudl
 *
 * @property-read Nette\Application\UI\ITemplate $template
 * @property-read string $snippetId
 */
abstract class Control extends PresenterComponent implements IRenderable
{
	/** @var Nette\Application\UI\ITemplate */
	private $template;

	/** @var array */
	private $invalidSnippets = array();

	/** @var bool */
	public $snippetMode;


	/********************* template factory ****************d*g**/


	/**
	 * @return Nette\Application\UI\ITemplate
	 */
	final public function getTemplate()
	{
		if ($this->template === NULL) {
			$value = $this->createTemplate();
			if (!$value instanceof Nette\Application\UI\ITemplate && $value !== NULL) {
				$class2 = get_class($value); $class = get_class($this);
				throw new Nette\UnexpectedValueException("Object returned by $class::createTemplate() must be instance of Nette\\Templating\\ITemplate, '$class2' given.");
			}
			$this->template = $value;
		}
		return $this->template;
	}


    protected function createTemplate()
    {
        $templateFactory = $this->getPresenter()->getTemplateFactory();
        return $templateFactory->createTemplate($this);
    }


	/**
	 * Descendant can override this method to customize template compile-time filters.
	 * @param  Nette\Templating\Template
	 * @return void
	 */
	public function templatePrepareFilters($template)
	{
	}

    /**
     * Forces control or its snippet to repaint.
     * @return void
     */
    public function redrawControl($snippet = null, $redraw = true)
    {
        if ($redraw) {
            $this->invalidSnippets[$snippet === null ? "\0" : $snippet] = true;

        } elseif ($snippet === null) {
            $this->invalidSnippets = [];

        } else {
            $this->invalidSnippets[$snippet] = false;
        }
    }


	/**
	 * Returns widget component specified by name.
	 * @param  string
	 * @return Nette\ComponentModel\IComponent
	 */
	public function getWidget($name)
	{
		trigger_error(__METHOD__ . '() is deprecated, use getComponent() instead.', E_USER_WARNING);
		return $this->getComponent($name);
	}


	/**
	 * Saves the message to template, that can be displayed after redirect.
	 * @param  string
	 * @param  string
	 * @return \stdClass
	 */
	public function flashMessage($message, $type = 'info')
	{
		$id = $this->getParameterId('flash');
		$messages = $this->getPresenter()->getFlashSession()->$id;
		$messages[] = $flash = (object) array(
			'message' => $message,
			'type' => $type,
		);
		$this->getTemplate()->flashes = $messages;
		$this->getPresenter()->getFlashSession()->$id = $messages;
		return $flash;
	}


	/********************* rendering ****************d*g**/


	/**
	 * Forces control or its snippet to repaint.
	 * @param  string
	 * @return void
	 */
	public function invalidateControl($snippet = NULL)
	{
		$this->invalidSnippets[$snippet] = TRUE;
	}


	/**
	 * Allows control or its snippet to not repaint.
	 * @param  string
	 * @return void
	 */
	public function validateControl($snippet = NULL)
	{
		if ($snippet === NULL) {
			$this->invalidSnippets = array();

		} else {
			unset($this->invalidSnippets[$snippet]);
		}
	}


	/**
	 * Is required to repaint the control or its snippet?
	 * @param  string  snippet name
	 * @return bool
	 */
	public function isControlInvalid($snippet = NULL)
	{
		if ($snippet === NULL) {
			if (count($this->invalidSnippets) > 0) {
				return TRUE;

			} else {
				$queue = array($this);
				do {
					foreach (array_shift($queue)->getComponents() as $component) {
						if ($component instanceof IRenderable) {
							if ($component->isControlInvalid()) {
								// $this->invalidSnippets['__child'] = TRUE; // as cache
								return TRUE;
							}

						} elseif ($component instanceof Nette\ComponentModel\IContainer) {
							$queue[] = $component;
						}
					}
				} while ($queue);

				return FALSE;
			}

		} else {
			return isset($this->invalidSnippets[NULL]) || isset($this->invalidSnippets[$snippet]);
		}
	}


	/**
	 * Returns snippet HTML ID.
	 * @param  string  snippet name
	 * @return string
	 */
	public function getSnippetId($name = NULL)
	{
		// HTML 4 ID & NAME: [A-Za-z][A-Za-z0-9:_.-]*
		return 'snippet-' . $this->getUniqueId() . '-' . $name;
	}

}
