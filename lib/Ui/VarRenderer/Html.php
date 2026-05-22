<?php

/**
 * This file contains all Horde_Core_Ui_VarRenderer extensions required for
 * editing calendars.
 *
 * See the enclosed file LICENSE for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @package Kronolith
 */

/**
 * The Kronolith_Ui_VarRenderer_Html class provides additional methods for
 * rendering Kronolith specific fields.
 *
 * @todo    Clean this hack up with Horde_Form/H6
 * @author  Michael J Rubinsky <mrubinsk@horde.org>
 * @package Kronolith
 */
class Kronolith_Ui_VarRenderer_Html extends Horde_Core_Ui_VarRenderer_Html
{
    /**
     * Render tag field.
     */
    protected function _renderVarInput_KronolithTags($form, $var, $vars)
    {
        $varname = htmlspecialchars($var->getVarName());
        $value = $var->getValue($vars);

        $html = sprintf('<input id="%s" type="text" name="%s" value="%s" />', $varname, $varname, $value);
        /**
         * ARCHITECTURE VIOLATION: Using deprecated Horde::img()
         * @deprecated Use Horde_Themes_Image::tag() instead
         * @see Horde_Deprecated::img()
         */
        $html .= sprintf(
            '<span id="%s_loading_img" style="display:none;">%s</span>',
            $varname,
            Horde::img('loading.gif', _("Loading..."))
        );

        $GLOBALS['injector']->getInstance('Horde_Core_Factory_Imple')
            ->create(
                'Kronolith_Ajax_Imple_TagAutoCompleter',
                ['id' => $varname]
            );
        return $html;
    }

}
