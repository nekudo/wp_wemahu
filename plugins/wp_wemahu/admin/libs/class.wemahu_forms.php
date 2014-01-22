<?php
/**
 * @package	wp_wemahu
 * @license GNU General Public License version 2 or later; see license.txt
 * @copyright nekudo.com
 * @author Simon Samtleben <support@nekudo.com>
 */
class Wemahu_Forms
{
	protected $options = array();

	public function __construct($options)
	{
		$this->options = $options;
	}

	/**
	 * Sanitizes form input data.
	 *
	 * @param mixed $input Input data from post/get request.
	 * @return mixed Sanitized input data.
	 */
	public function sanitizeInput($input)
	{
		$input['use_api'] = (int)$input['use_api'];
		$input['cron_ruleset'] = (int)$input['cron_ruleset'];
		$input['cron_sendmail'] = (int)$input['cron_sendmail'];
		$input['cron_emptyreport'] = (int)$input['cron_emptyreport'];
		$input['cron_email'] = filter_var($input['cron_email'], FILTER_SANITIZE_EMAIL);
		if(empty($input['cron_email']))
		{
			$input['cron_email'] = '';
		}
		return $input;
	}

	/**
	 * Displays a text-input field.
	 *
	 * Hint: Possible array keys for arguments are:
	 * name*, desc, group, id, classes
	 *
	 * @param $args array Settings defining the input behavior.
	 * @return bool True on success false in case of error.
	 */
	public function displayTextInput($args)
	{
		if(empty($args['name']))
		{
			return false;
		}
		$fieldName = $args['name'];
		$fieldDesc = (!empty($args['desc'])) ? ' ' . $args['desc'] : '';
		$groupName = (!empty($args['group'])) ? $args['group'] : '';
		$fieldNameOutput = (!empty($groupName)) ? $groupName . '[' . $fieldName . ']' : $fieldName;
		$fieldValue = (isset($this->options[$fieldName])) ? $this->options[$fieldName] : '';
		$fieldId = (!empty($args['id'])) ? $args['id'] : '';
		$fieldClasses = (!empty($args['classes'])) ? $args['classes'] : '';

		$output = '<input type="text" ';
		$output.= (!empty($fieldId)) ? 'id="' . $fieldId . '" ' : '';
		$output.= (!empty($fieldClasses)) ? 'class="' . $fieldClasses . '" ' : '';
		$output.= 'name="' . $fieldNameOutput . '" value="' . $fieldValue . '" />';
		$output.= (!empty($fieldDesc)) ? '<p class="description">' . $fieldDesc . '</p>' : '';
		echo $output;
		return true;
	}

	/**
	 * Displays a checkbox-input field.
	 *
	 * Hint: Possible array keys for arguments are:
	 * name*, desc, group, id, classes
	 *
	 * @param $args array Settings defining the input behavior.
	 * @return bool True on success false in case of error.
	 */
	public function displayCheckboxInput($args)
	{
		if(empty($args['name']))
		{
			return false;
		}
		$fieldName = $args['name'];
		$fieldDesc = (!empty($args['desc'])) ? ' ' . $args['desc'] : '';
		$groupName = (!empty($args['group'])) ? $args['group'] : '';
		$fieldNameOutput = (!empty($groupName)) ? $groupName . '[' . $fieldName . ']' : $fieldName;
		$fieldValue = (isset($this->options[$fieldName])) ? $this->options[$fieldName] : '';
		$fieldId = (!empty($args['id'])) ? $args['id'] : '';
		$fieldClasses = (!empty($args['classes'])) ? $args['classes'] : '';

		$output = '<label for="' . $fieldId . '">';
		$output.= '<input type="checkbox" value="1" ';
		$output.= ($fieldValue === 1) ? 'checked="checked" ' : '';
		$output.= (!empty($fieldId)) ? 'id="' . $fieldId . '" ' : '';
		$output.= (!empty($fieldClasses)) ? 'class="' . $fieldClasses . '" ' : '';
		$output.= 'name="' . $fieldNameOutput . '" />';
		$output.= $fieldDesc;
		$output.= '</label>';
		echo $output;
		return true;
	}

	/**
	 * Displays a dropdown-input field.
	 *
	 * Hint: Possible array keys for arguments are:
	 * name*, desc, group, id, classes, values
	 *
	 * @param $args array Settings defining the input behavior.
	 * @return bool True on success false in case of error.
	 */
	public function displayDropdownInput($args)
	{
		if(empty($args['name']))
		{
			return false;
		}

		$fieldName = $args['name'];
		$fieldDesc = (!empty($args['desc'])) ? ' ' . $args['desc'] : '';
		$groupName = (!empty($args['group'])) ? $args['group'] : '';
		$fieldNameOutput = (!empty($groupName)) ? $groupName . '[' . $fieldName . ']' : $fieldName;
		$fieldValue = (isset($this->options[$fieldName])) ? $this->options[$fieldName] : '';
		$fieldId = (!empty($args['id'])) ? $args['id'] : '';
		$fieldClasses = (!empty($args['classes'])) ? $args['classes'] : '';

		$output = '<select ';
		$output.= (!empty($fieldId)) ? 'id="' . $fieldId . '" ' : '';
		$output.= (!empty($fieldClasses)) ? 'class="' . $fieldClasses . '" ' : '';
		$output.= 'name="' . $fieldNameOutput . '">';
		foreach($args['values'] as $key => $value)
		{
			$selected = ($key == $fieldValue) ? ' selected="selected"' : '';
			$output.='<option value="' . $key . '"' . $selected . '>' . $value . '</option>';
		}
		$output.= '</select>';
		$output.= (!empty($fieldDesc)) ? '<p class="description">' . $fieldDesc . '</p>' : '';
		echo $output;
		return true;
	}

	/**
	 * Displays section description for Wemahu general settings.
	 *
	 * @return bool Always true.
	 */
	public function displaySectionInfoGeneral()
	{
		echo "Here you can configure the general behaviour of the component.";
		return true;
	}

	/**
	 * Displays section description for Wemahu cronjob settings.
	 *
	 * @return bool Always true.
	 */
	public function displaySectionInfoCronjob()
	{
		echo "This settings apply to Wemahu cronjobs.";
		return true;
	}
}