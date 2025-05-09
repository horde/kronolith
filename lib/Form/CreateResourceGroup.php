<?php

/**
 * Horde_Form for creating resource groups.
 *
 * See the enclosed file LICENSE for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @package Kronolith
 */

/**
 * The Kronolith_CreateResourceGroupForm class provides the form for creating
 * a resource group.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Kronolith
 */
class Kronolith_Form_CreateResourceGroup extends Horde_Form
{
    /**
     * @throws Kronolith_Exception
     */
    public function __construct($vars)
    {
        parent::__construct($vars, _("Create Resource Group"));

        $resources = Kronolith::getDriver('Resource')->listResources(Horde_Perms::READ, ['isgroup' => 0]);
        $enum = [];
        foreach ($resources as $resource) {
            $enum[$resource->getId()] = htmlspecialchars($resource->get('name'));
        }
        $this->addVariable(_("Name"), 'name', 'text', true);
        $this->addVariable(_("Description"), 'description', 'longtext', false, false, null, [4, 60]);
        $this->addVariable(_("Resources"), 'members', 'multienum', false, false, null, ['enum' => $enum]);
        $this->setButtons([_("Create")]);
    }

    public function execute()
    {
        $new = ['name' => $this->_vars->get('name'),
            'description' => $this->_vars->get('description'),
            'members' => $this->_vars->get('members')];
        Kronolith_Resource::addResource(new Kronolith_Resource_Group($new));
    }
}
