<?php namespace Vaprogui\UI;

use Exception;

class Form
{
	protected $data;

	/**
	 * @param array $data Data processed by Vaprogui\Ruby\Reader
	 */
	public function __construct(array $data)
	{
		$this->data = $data;
	}

	public function outputForm()
	{
		$return = '';

		foreach ($this->data as $item)
		{
			$return .= $this->routeItem($item);
		}

		return $return;
	}

	protected function routeItem($item)
	{
		switch ($item['type']) {
			case 'array':
				return $this->itemArray($item);
				break;
			case 'object':
				return $this->itemObject($item);
				break;
			case 'code':
				return $this->itemCode($item);
				break;
			case 'config':
				return $this->itemConfig($item);
				break;
			case 'comment':
				return $this->itemComment($item);
				break;
			case 'blank':
				return $this->itemBlank($item);
				break;
			default:
				throw new Exception('Don\'t know how to handle item type ' . $item['type']);
		}
	}

	protected function itemObject(array $item)
	{
		return; // @todo;
	}

	protected function itemCode(array $item)
	{
		$return = '';

		$return .= '<code>' . $item['data'] . '</code>';

		return $return;
	}

	protected function itemArray(array $item)
	{
		return; // @todo;
	}

	protected function itemConfig(array $item)
	{
		$return = '';
		$return .= '<label>' . $item['data']['key'] . '</label>';
		$return .= '<span class="inline-comment">' . $item['data']['value']['comment'] . '</span><br>';
		$return .= '<input type="text" value="' . $item['data']['value']['value'] . '">';
		$return .= '<br>';

		return $return;
	}

	protected function itemComment(array $item)
	{
		return '<span class="comment">' . $item['original_lines'][0] . '</span><br>';
	}

	protected function itemBlank(array $item)
	{
		return '<br>';
	}


}