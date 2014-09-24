<?php

require_once 'CRM/Core/Page.php';

class CRM_Genericsourcereader_Page_CopyTables extends CRM_Core_Page {
  function run() {
    // Example: Set the page-title dynamically; alternatively, declare a static title in xml/Menu/*.xml
    CRM_Utils_System::setTitle(ts('CopyTables'));

    // Example: Assign a variable for use in a template
    $this->assign('currentTime', date('Y-m-d H:i:s'));

	
	// ### Basic definitions for referencing ##########################################################################
	
	$db = 'civicrm_test3'; // === may need to modify this ======================================================
	$baseDir = ''; // currently set to CMS root dir.
	$logName = 'generic.copytables.log';
	$logExport = fopen($baseDir . $logName, 'w');
	$eol = "\r\n";
	$tab = "\t";
	$lblOk = "   ";
	$lblErr = ">> ";
		
	
	// ### Caching for referencing  ###################################################################################
	
	// e.g. 'extends_entity_column_values' in custom groups and option groups in custom fields
	// we will often find id numbers in columns, but these refer to record in the source database
	// we will need the names for referencing while setting up the target environment
	$entities = array();
	
	// cache 'Activity' -----------------------------------------------------------------------------------------------
	$key = 'Activity';
	fwrite($logExport, $lblOk . "Caching " . $key . $eol);
	$entities[$key] = array();
	$sql = 'SELECT ogv.value, ogv.name FROM ' . $db . '.civicrm_option_value ogv, civicrm_option_group ogp WHERE ogv.option_group_id = ogp.id AND ogp.name = \'activity_type\'';
	$dao = CRM_Core_DAO::executeQuery($sql);
	while($dao->fetch()) {
		$entities[$key][$dao->value] = $dao->name;
	}
	
	// cache 'Address' ------------------------------------------------------------------------------------------------
	// skipped for now: do we need it? can 'extends_entity_column_value' be different that NULL?
	
	// cache 'Case' ---------------------------------------------------------------------------------------------------
	$key = 'Case';
	fwrite($logExport, $lblOk . "Caching " . $key . $eol);
	$entities[$key] = array();
	$sql = 'SELECT ogv.value, ogv.name FROM ' . $db . '.civicrm_option_value ogv, civicrm_option_group ogp WHERE ogv.option_group_id = ogp.id AND ogp.name = \'case_type\'';
	$dao = CRM_Core_DAO::executeQuery($sql);
	while($dao->fetch()) {
		$entities[$key][$dao->value] = $dao->name;
	}
	
	// cache 'Contact Types ('Individual', 'Organization' etc.) -------------------------------------------------------
	$key = 'Contact Type';
	fwrite($logExport, $lblOk . "Caching " . $key . $eol);
	$entities[$key] = array();
	$sql = 'SELECT ifnull(p.name, c.name) parent, c.id, c.name FROM ' . $db . '.civicrm_contact_type p RIGHT JOIN ' . $db . '.civicrm_contact_type c ON c.parent_id = p.id ORDER BY ifnull(c.parent_id, c.id), c.id';
	$dao = CRM_Core_DAO::executeQuery($sql);
	while($dao->fetch()) {
		if (!array_key_exists($dao->parent, $entities)) {
			$entities[$dao->parent] = array();
		}
		$entities[$dao->parent][$dao->id] = $dao->name;
		$entities[$key][$dao->id] = $dao->name;
	}
	
	// cache 'Membership' ---------------------------------------------------------------------------------------------
	$key = 'Membership';
	fwrite($logExport, $lblOk . "Caching " . $key . $eol);
	$entities[$key] = array();
	$sql = 'SELECT typ.id, typ.name FROM ' . $db . '.civicrm_membership_type typ';
	$dao = CRM_Core_DAO::executeQuery($sql);
	while($dao->fetch()) {
		$entities[$key][$dao->id] = $dao->name;
	}
	
	// cache 'Participant' --------------------------------------------------------------------------------------------
	// skipped for now: do we need it? what is a "participant"? can 'extends_entity_column_value' be different that NULL?
	
	// cache 'Relationship' -------------------------------------------------------------------------------------------
	$key = 'Relationship';
	fwrite($logExport, $lblOk . "Caching " . $key . $eol);
	$entities[$key] = array();
	$sql = 'SELECT typ.id, typ.name_b_a name FROM ' . $db . '.civicrm_relationship_type typ';
	$dao = CRM_Core_DAO::executeQuery($sql);
	while($dao->fetch()) {
		$entities[$key][$dao->id] = $dao->name;
	}
	
	// cache 'Option groups' ------------------------------------------------------------------------------------------
	$key = 'Option Group';
	fwrite($logExport, $lblOk . "Caching " . $key . $eol);
	$entities[$key] = array();
	$sql = 'select ogp.id, ogp.name from ' . $db . '.civicrm_option_group ogp where ogp.id in (select distinct fld.option_group_id from ' . $db . '.civicrm_custom_field fld where !isnull(fld.option_group_id))';
	$dao = CRM_Core_DAO::executeQuery($sql);
	while($dao->fetch()) {
		$entities[$key][$dao->id] = $dao->name;
	}
	
	// cache 'Group' --------------------------------------------------------------------------------------------------
	$key = 'Group';
	fwrite($logExport, $lblOk . "Caching " . $key . $eol);
	$entities[$key] = array();
	$sql = 'select typ.id, typ.name from ' . $db . '.civicrm_group typ';
	$dao = CRM_Core_DAO::executeQuery($sql);
	while($dao->fetch()) {
		$entities[$key][$dao->id] = $dao->name;
	}
	
	// cache 'Tag' ----------------------------------------------------------------------------------------------------
	$key = 'Tag';
	fwrite($logExport, $lblOk . "Caching " . $key . $eol);
	$entities[$key] = array();
	$sql = 'select typ.id, typ.name from ' . $db . '.civicrm_tag typ';
	$dao = CRM_Core_DAO::executeQuery($sql);
	while($dao->fetch()) {
		$entities[$key][$dao->id] = $dao->name;
	}
	
	// cache 'Component' ----------------------------------------------------------------------------------------------
	$key = 'Component';
	fwrite($logExport, $lblOk . "Caching " . $key . $eol);
	$entities[$key] = array();
	$sql = 'select typ.id, typ.name from ' . $db . '.civicrm_component typ';
	$dao = CRM_Core_DAO::executeQuery($sql);
	while($dao->fetch()) {
		$entities[$key][$dao->id] = $dao->name;
	}
	
	
	// in situations where entities have a parent/child relation, we need to make sure the parent exists, before we attempt to add the child
	// for this purpose, we sort the entities by id and by default obey that order
	foreach ($entities as $key=>$value) {
		ksort($entities[$key]);
	}
	
	fwrite($logExport, $eol);
	

//	dpm($entities, 'Entities for id to name translations'); // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	
	
	// ### The entities that need to be cast in the 'generic' extension ###############################################
	// a.k.a. 'Betty's List'
	
	$required = array(
		'Contact Type' => array(
			'Expert',
			'Customer',
			'Donor',
			'Partner',
			'Country',
		),
		'Group'=> array(
			'Experts',
			'Candidate Expert',
			'Programme Managers',
			'Sector Coordinators',
			'Country Coordinators',
			'Project Officers',
			'Newsletter',
			'Representatives',
		),
		'Relationship Type' => array( // label_a_b
			'Anamon',
			'Country Coordinator is',
			'CV Intake',
			'Expert',
			'Has authorised',
			'Head Personnel and Organisation',
			'ICE (In Case of Emergency) call',
			'Project Officer',
			'Projectmanager',
			'Recruitment Team Member',
			'Representative is',
			'Sector Coordinator',
			'Sector Support Officer',
		),
		'Option Group' => array( // title; check individual values against Betty's original list
			'Case Type',
			'Case Status',
			'case_type_code',
		),
		'Custom Field Group' => array(
			'Customer Data',
			'Yearly information',
			'Additional Data',
			'Projectinformation',
			'Assessment Projectrequest by Rep',
			'Passport Information',
			'Languages',
			'RCT Intake Report',
			'Rejection Application New Expert',
			'Work Experience',
			'Medical Information',
			'Bank Information',
			'Expert Data',
			'Education',
			'Intake Customer by CC',
			'Intake Customer by SC',
			'Intake Customer by Anamon',
		),
		'Tag' => array(
			'Customer',
			'Partner',
			'Expert',
			'Sector',
			'Current customer',
			'Former Customer',
			'Prospect Customer',
			'Rejected Customer',
			'Suspect Customer',
			'New Customer',
			'Current Partner',
			'Former Partner',
			'Hot Prospect Partner',
			'Lost Contact Partner',
			'Prospect Partner',
			'Suspect Partner',
			'Candidate Expert',
			'Former Expert',
			'Hotel services',
			'Front office services',
			'Housekeeping services',
			'Food & beverages services/restaurant/bar',
			'Food & beverages services/kitchen',
			'Spa, sports & leisure services',
			'Building maintenance',
			'Kitchen maintenance',
			'Events management',
			'Hospitality training/coaching',
			'Facility management',
			'ICT systems for hotels',
			'Hotel management',
			'HOSPITALITY, LARGE HOTELS',
			'HOSPITALITY, SMALL HOTELS',
			'RESTAURANTS, CATERING & EVENTS',
			'TOURISM & RECREATIONAL SERVICES',
			'METAL CONSTRUCTION, MAINTENANCE & REPAIR',
			'METAL PROCESSING',
			'METAL MACHINE ENGINEERING & CONSTRUCTION- SYSTEM',
			'AIRCRAFT MAINTENANCE & SHIPBUILDING & REPAIR',
			'VOCATIONAL EDUCATION & TRAINING',
			'TIMBER PROCESSING',
			'FURNITURE MANUFACTURING AND SHOPFITTING',
			'TRANSPORT & LOGISTICS',
			'WHOLESALE, BUSINESS TO BUSINESS',
			'RETAIL, BUSINESS TO CONSUMER',
			'TEXTILES INDUSTRY AND CONSUMER GOODS',
			'LEATHER INDUSTRY AND CONSUMER GOODS',
			'ANIMAL FEED PRODUCTION',
			'BEEKEEPING',
			'DAIRY PROCESSING & PRODUCTS',
			'FISHERIES AND FISH PROCESSING',
			'PIG FARMING',
			'POULTRY FARMING',
			'RUMINANT (CATTLE, SHEEP, GOATS, CAMELS, HORSES ETC.)',
			'PRINTING, CROSS MEDIA & PUBLISHING',
			'PACKAGING',
			'PAPER & BOARD PRODUCTS',
			'HEALTH CARE SERVICES',
			'TRADE UNIONS',
			'GOVERNMENT SERVICES',
			'OILS (EDIBLE & FATS)',
			'MEAT PROCESSING',
			'FOOD PROCESSING',
			'BEVERAGES PRODUCTION',
			'BAKERY, PASTRY & CONFECTIONARY',
			'Small hotel/B&B services',
			'Housekeeping services (small hotels)',
			'Front office services (small hotels)',
			'Food & beverages services/restaurant/bar (small hotels)',
			'Food & beverages services/kitchen (small hotels)',
			'Sports & leisure services',
			'Building maintenance (small hotels)',
			'Kitchen maintenance (small hotels)',
			'Small hotel management',
			'Restaurant, management services',
			'Restaurant, menu engineering',
			'Restaurant, management kitchen',
			'Restaurant, cooking, western/asian/pizza',
			'Restaurant, stock/food safety, HACCP',
			'Education/training; curriculum/train the trainer',
			'Catering, management and marketing',
			'Catering, kitchen, food & beverages, food safety',
			'Events, organisation, logistics, (E)-marketing',
			'Meetings, incentives, conferences. exhibitions (MICE)',
			'Travel agent (incoming/outgoing)',
			'Travel agent, E-marketing, dynamic packaging',
			'Travel agent (incoming/outgoing), management',
			'Tour operator, marketing',
			'Tour operator, management, logistics',
			'Tourist Board, tourism strategy/policy',
			'Tourist Board, training, PR',
			'Processing raw materials',
			'Manufacturing finishing products',
			'Machine parts, spare parts',
			'Machines, equipment',
			'Vehicles, vessels',
			'Structures',
			'Mechanisation',
			'Quality assurance metal construction',
			'Workshop management',
			'Workshop management Metal processing',
			'Metal processing equipment maintenance',
			'Metal processing equipment operations',
			'Melting',
			'Refining',
			'Sand and die casting',
			'Casting',
			'Heat treatment',
			'Hot/cold rolling',
			'Forging',
			'Extruding',
			'Drawing',
			'Surface treatment',
			'Product processing Metallurgy and alloying',
			'Workshop management equipment making',
			'Quality assurance metal construction (2)',
			'Agriculture equipment',
			'Textile equipment',
			'Pulp & paper equipment',
			'Chemical equipment',
			'Food equipment',
			'Packaging equipment',
			'Internal transport equipment',
			'Boiler equipment',
			'Heat exchanges equipment',
			'Pressure vessels equipment',
			'Heating/cooling equipment',
			'Welding equipment',
			'Workshop management (2)',
			'Small aircraft maintenance',
			'Ship design (sea and river going vessels)',
			'Ship construction (metal, wood, polyesthers)',
			'Ship maintenance',
			'Assistance in developing new curricula',
			'Assistance in adapting existing curricula',
			'Help in reconstructing curricula',
			'Implementation of new studies',
			'Advise on methodology',
			'Advise on how to teach (theories/practical skills, etc.)',
			'Advise on design/managing new programmes',
			'Advise on design/managing new laboratories',
			'Assistance in the development of teaching materials',
			'Train-the-trainers/teach-the-teachers programmes',
			'Restructuring (management) of school organisation',
			'Training of management of educational instituteS',
			'Setting up a new department',
			'Setting up a new programme',
			'Setting up a multimedia centre, a library',
			'Introducing an automation system, E-learning, etc.',
			'Education/training; problem based learning',
		),
		'Activity Type' => array (
			'Interview',
			'KIT test',
			'RCT Intake Report',
			'Create Candidate Expert Account',
			'Fill Out PUM CV',
			'Contact with Colleague',
			'Intake Customer by CC',
			'Intake Customer by SC',
			'Intake Customer by Anamon',
			'Assessment Project Request by Rep',
		),
	);
	
//	dpm($required, 'required (Betty\'s list'); // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	
	
	// ### Build definition arrays for 'generic' extension ############################################################
	
	// Contact Types --------------------------------------------------------------------------------------------------
	$key = 'Contact Type';
	fwrite($logExport, $lblOk . 'Start processing ' . $key . $eol);
	try{
		$def = $required[$key];
		
		try {
			$fileName = 'generic.contacttype.def.inc.php';
			$fileExport = fopen($baseDir . $fileName, 'w');
			
			fwrite($fileExport, "<?php" . $eol . $eol);
			fwrite($fileExport, "class Generic_ContactType_Def {" . $eol . $eol);
			fwrite($fileExport, $tab . "// definitions for: " . $key . $eol . $eol);
			fwrite($fileExport, $tab . "static function required() {" . $eol);
			fwrite($fileExport, $tab . $tab . "return array(" .$eol);
			
			foreach($def as $elm) {
				try {
					fwrite($logExport, $lblOk . "Processing " . $elm . $eol);
					$sql = 'select * from ' . $db . '.civicrm_contact_type where label=\'' . $elm . '\'';
					try{
						$dao = CRM_Core_DAO::executeQuery($sql);
						$dao->fetch();
					} catch(Exception $e) {
						throw new Exception("Query for " . $elm . " failed");
					}
					if ($dao->N == 0) {;
						fwrite($logExport, $lblErr . "Query for " . $elm . " did not deliver any results!" . $eol);
					} else {
						fwrite($fileExport, $tab . $tab . $tab . "array(" . $eol);
						
						fwrite($fileExport, $tab . $tab . $tab . $tab . "'name' => '" . $dao->name . "'," . $eol);
						fwrite($fileExport, $tab . $tab . $tab . $tab . "'label' => '" . addslashes($dao->label) . "'," . $eol);
						fwrite($fileExport, $tab . $tab . $tab . $tab . "'parent' => '" . $entities['Contact Type'][$dao->parent_id] . "'," . $eol);
						fwrite($fileExport, $tab . $tab . $tab . $tab . "'description' => '" . addslashes($dao->description) . "'," . $eol);
						
						fwrite($fileExport, $tab . $tab . $tab . ")," . $eol);
					}
				} catch(Exception $e) {
					fwrite($logExport, $lblErr . $e->getMessage() . $eol);
				}
			}
			
			fwrite($fileExport, $tab . $tab . ");" . $eol);
			fwrite($fileExport, $tab . "}" . $eol);
			
			fwrite($fileExport, "}" . $eol);
			
			fclose($fileExport);
		} catch(Exception $e) {
			fwrite($logExport, $lblErr . $e->getMessage() . $eol);
		}
	} catch(Exception $e) {
		fwrite($logExport, $lblErr . 'No definitions found' . $eol);
	}
	fwrite($logExport, $lblOk . 'Finished processing ' . $key . $eol . $eol);

	
	// Groups ---------------------------------------------------------------------------------------------------------
	$key = 'Group';
	fwrite($logExport, $lblOk . 'Start processing ' . $key . $eol);
	try{
		$def_tmp = $required[$key];
		$def = array();
		// sort definitions by their parent id first
		$sql_def = 'select * from ' . $db . '.civicrm_group where title in (' . $this->_strArForSql($def_tmp) . ') order by ifnull(parents, 0), id';

		try{
			$dao_def = CRM_Core_DAO::executeQuery($sql_def);
			while ($dao_def->fetch()) {
				$def[] = $dao_def->title;
			}
		} catch(Exception $e) {
			throw new Exception("Failed to apply correct parent/child order");
		}
		
		// report the requested elements that got lost in the query for sorting
		$def_lost = array_udiff($def_tmp, $def, 'strcasecmp');
		foreach($def_lost as $elm) {
			fwrite($logExport, $lblErr . "Query for " . $elm . " did not deliver any results!" . $eol);
		}
		
		try {
			$fileName = 'generic.group.def.inc.php';
			$fileExport = fopen($baseDir . $fileName, 'w');
			
			fwrite($fileExport, "<?php" . $eol . $eol);
			fwrite($fileExport, "class Generic_Group_Def {" . $eol . $eol);
			fwrite($fileExport, $tab . "// definitions for: " . $key . $eol . $eol);
			fwrite($fileExport, $tab . "static function required() {" . $eol);
			fwrite($fileExport, $tab . $tab . "return array(" .$eol);
			
			foreach($def as $elm) {
				try {
					fwrite($logExport, $lblOk . "Processing " . $elm . $eol);
					$sql = 'select * from ' . $db . '.civicrm_group where title=\'' . $elm . '\'';
					try{
						$dao = CRM_Core_DAO::executeQuery($sql);
						$dao->fetch();
					} catch(Exception $e) {
						throw new Exception("Query for " . $elm . " failed");
					}
					if ($dao->N == 0) {;
						fwrite($logExport, $lblErr . "Query for " . $elm . " did not deliver any results!" . $eol);
					} else {
						fwrite($fileExport, $tab . $tab . $tab . "array(" . $eol);
						
						fwrite($fileExport, $tab . $tab . $tab . $tab . "'module' => 'nl.pum.generic'," . $eol);
						fwrite($fileExport, $tab . $tab . $tab . $tab . "'name' => '" . $dao->name . "'," . $eol);
						fwrite($fileExport, $tab . $tab . $tab . $tab . "'entity' => 'Group'," . $eol);
						fwrite($fileExport, $tab . $tab . $tab . $tab . "'params' => array(" . $eol);
						
						fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . "'version' => 3," . $eol);
						fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . "'name' => '" . $dao->name . "'," . $eol);
						fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . "'title' => '" . addslashes($dao->title) . "'," . $eol);
						fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . "'description' => '" . addslashes($dao->description) . "'," . $eol);
						if (!is_null($dao->parents)) {
							// assume only 1 single parent
							fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . "'parent' => '" . $entities['Group'][$dao->parents] . "'," . $eol);
						}
						fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . "'is_active' => 1," . $eol);
						if (!empty($dao->group_type)) {
							// availability of enties in option group 'group_type' not checked yet
							fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . "'group_type' => array(" . $eol);
						
							$typ = explode(chr(1), $dao->group_type);
							foreach($typ as $typ_key=>$typ_value) {
								if (!empty($typ_value)) {
									fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . $tab . $typ_value . " => 1," . $eol);
								}
							}
							fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . ")," . $eol);
						}
						
						fwrite($fileExport, $tab . $tab . $tab . $tab . ")," . $eol);
						fwrite($fileExport, $tab . $tab . $tab . ")," . $eol);
					}
				} catch(Exception $e) {
					fwrite($logExport, $lblErr . $e->getMessage() . $eol);
				}
			}
			
			fwrite($fileExport, $tab . $tab . ");" . $eol);
			fwrite($fileExport, $tab . "}" . $eol);
			
			fwrite($fileExport, "}" . $eol);
			
			fclose($fileExport);
		} catch(Exception $e) {
			fwrite($logExport, $lblErr . $e->getMessage() . $eol);
		}
	} catch(Exception $e) {
		fwrite($logExport, $lblErr . 'No definitions found' . $eol);
	}
	fwrite($logExport, $lblOk . 'Finished processing ' . $key . $eol . $eol);
	
	
	// Relationship Types ---------------------------------------------------------------------------------------------
	$key = 'Relationship Type';
	fwrite($logExport, $lblOk . 'Start processing ' . $key . $eol);
	try{
		$def = $required[$key];
		
		try {
			$fileName = 'generic.relationshiptype.def.inc.php';
			$fileExport = fopen($baseDir . $fileName, 'w');
			
			fwrite($fileExport, "<?php" . $eol . $eol);
			fwrite($fileExport, "class Generic_RelationshipType_Def {" . $eol . $eol);
			fwrite($fileExport, $tab . "// definitions for: " . $key . $eol . $eol);
			fwrite($fileExport, $tab . "static function required() {" . $eol);
			fwrite($fileExport, $tab . $tab . "return array(" .$eol);
			
			foreach($def as $elm) {
				try {
					fwrite($logExport, $lblOk . "Processing " . $elm . $eol);
					$sql = 'select * from ' . $db . '.civicrm_relationship_type where label_a_b=\'' . $elm . '\'';
					try{
						$dao = CRM_Core_DAO::executeQuery($sql);
						$dao->fetch();
					} catch(Exception $e) {
						throw new Exception("Query for " . $elm . " failed");
					}
					if ($dao->N == 0) {;
						fwrite($logExport, $lblErr . "Query for " . $elm . " did not deliver any results!" . $eol);
					} else {
						fwrite($fileExport, $tab . $tab . $tab . "array(" . $eol);
						
						fwrite($fileExport, $tab . $tab . $tab . $tab . "'module' => 'nl.pum.generic'," . $eol);
						fwrite($fileExport, $tab . $tab . $tab . $tab . "'name' => '" . $elm . "'," . $eol);
						fwrite($fileExport, $tab . $tab . $tab . $tab . "'entity' => 'RelationshipType'," . $eol);
						fwrite($fileExport, $tab . $tab . $tab . $tab . "'params' => array(" . $eol);
						
						fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . "'version' => 3," . $eol);
						fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . "'name_a_b' => '" . addslashes($dao->name_a_b) . "'," . $eol);
						fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . "'name_b_a' => '" . addslashes($dao->name_b_a) . "'," . $eol);
						fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . "'label_a_b' => '" . addslashes($dao->label_a_b) . "'," . $eol);
						fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . "'label_b_a' => '" . addslashes($dao->label_b_a) . "'," . $eol);
						$tmp=is_null($dao->contact_type_a)?'':$dao->contact_type_a;
						fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . "'contact_type_a' => '" . $tmp . "'," . $eol);
						$tmp=is_null($dao->contact_sub_type_a)?'':$dao->contact_sub_type_a;
						fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . "'contact_sub_type_a' => '" . $tmp . "'," . $eol);
						$tmp=is_null($dao->contact_type_b)?'':$dao->contact_type_b;
						fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . "'contact_type_b' => '" . $tmp . "'," . $eol);
						$tmp=is_null($dao->contact_sub_type_b)?'':$dao->contact_sub_type_b;
						fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . "'contact_sub_type_b' => '" . $tmp . "'," . $eol);
						fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . "'description' => '" . addslashes($dao->description) . "'," . $eol);
						fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . "'is_active' => " . $dao->is_active . "," . $eol);
						
						fwrite($fileExport, $tab . $tab . $tab . $tab . ")," . $eol);
						
						fwrite($fileExport, $tab . $tab . $tab . ")," . $eol);
					}
				} catch(Exception $e) {
					fwrite($logExport, $lblErr . $e->getMessage() . $eol);
				}
			}
			
			fwrite($fileExport, $tab . $tab . ");" . $eol);
			fwrite($fileExport, $tab . "}" . $eol);
			
			fwrite($fileExport, "}" . $eol);
			
			fclose($fileExport);
		} catch(Exception $e) {
			fwrite($logExport, $lblErr . $e->getMessage() . $eol);
		}
	} catch(Exception $e) {
		fwrite($logExport, $lblErr . 'No definitions found' . $eol);
	}
	fwrite($logExport, $lblOk . 'Finished processing ' . $key . $eol . $eol);
	
	
	// Option Groups --------------------------------------------------------------------------------------------------
	$key = 'Option Group';
	fwrite($logExport, $lblOk . 'Start processing ' . $key . $eol);
	try{
		$def = $required[$key];
		
		// first we need to consult the Custom Fields definitions, as custom fields may require additional Option Groups
		$key_fld = 'Custom Field Group';
		$def_fld = $required[$key_fld];
		$sql_fld = '
SELECT
  ogrp.title
FROM
  ' . $db . '.civicrm_custom_group cgrp,
  ' . $db . '.civicrm_custom_field cfld
  JOIN ' . $db . '.civicrm_option_group ogrp ON ogrp.id = cfld.option_group_id
WHERE
  cfld.custom_group_id = cgrp.id AND
  cgrp.title IN (' . $this->_strArForSql($def_fld) . ')
		';
		$dao_fld = CRM_Core_DAO::executeQuery($sql_fld);
		while ($dao_fld->fetch()) {
			$tmp = $dao_fld->title;
			if (!in_array($tmp, $def)) {
				$def[] = $tmp;
			}
		}
		
		try {
			$fileName = 'generic.optiongroup.def.inc.php';
			$fileExport = fopen($baseDir . $fileName, 'w');
			
			fwrite($fileExport, "<?php" . $eol . $eol);
			fwrite($fileExport, "class Generic_OptionGroup_Def {" . $eol . $eol);
			fwrite($fileExport, $tab . "// definitions for: " . $key . $eol . $eol);
			fwrite($fileExport, $tab . "static function required() {" . $eol);
			fwrite($fileExport, $tab . $tab . "return array(" .$eol);
			
			foreach($def as $elm) {
				try {
					fwrite($logExport, $lblOk . "Processing " . $elm . $eol);
					$sql = 'select * from ' . $db . '.civicrm_option_group where title=\'' . addslashes($elm) . '\'';
					try{
						$dao = CRM_Core_DAO::executeQuery($sql);
						$dao->fetch();
					} catch(Exception $e) {
						throw new Exception("Query for " . $elm . " failed");
					}
					if ($dao->N == 0) {;
						fwrite($logExport, $lblErr . "Query for " . $elm . " did not deliver any results!" . $eol);
					} else {
						fwrite($fileExport, $tab . $tab . $tab . "array(" . $eol);
						
						fwrite($fileExport, $tab . $tab . $tab . $tab . "'group_name' => '" . $dao->name . "'," . $eol);
						fwrite($fileExport, $tab . $tab . $tab . $tab . "'group_title' => '" . addslashes($dao->title) . "'," . $eol);
						fwrite($fileExport, $tab . $tab . $tab . $tab . "'values' => array(" . $eol);
						
						// start loop option value (within group)
						$sql_val = '
SELECT
  data.*,
  @rn := @rn + 10 rank
FROM
  (SELECT
     ovl.*
   FROM
     ' . $db . '.civicrm_option_group ogp,
     ' . $db . '.civicrm_option_value ovl
   WHERE
     ovl.option_group_id = ogp.id AND
     ogp.title = \'' . addslashes($dao->title) . '\'
   ORDER BY
     value) data,
  (SELECT
     @rn := 0) rnknr
						';
						try{
							$dao_val = CRM_Core_DAO::executeQuery($sql_val);
							if ($dao_val->N == 0) {
								fwrite($logExport, $lblErr . "Query for values in " . $elm . " did not deliver any results!" . $eol);
							} else {
								while($dao_val->fetch()) {
									fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . "array(" . $eol);
									fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . $tab . "'name' => '" . $dao_val->name . "'," . $eol);
									fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . $tab . "'label' => '" . addslashes($dao_val->label) . "'," . $eol);
									fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . $tab . "'value' => '" . addslashes($dao_val->value) . "'," . $eol);
									fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . $tab . "'weight' => " . $dao_val->rank . "," . $eol);
									$tmp = is_null($dao_val->description)?'':$dao_val->description;
									fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . $tab . "'description' => '" . addslashes($tmp) . "'," . $eol);
									fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . ")," . $eol);
								}
							}
						} catch(Exception $e) {
							throw new Exception("Error processing values for " . $elm . ": " . $e->getMessage());
						}
						// end loop option value (within group)
						
						fwrite($fileExport, $tab . $tab . $tab . $tab . ")," . $eol);
						
						fwrite($fileExport, $tab . $tab . $tab . ")," . $eol);
					}
				} catch(Exception $e) {
					fwrite($logExport, $lblErr . $e->getMessage() . $eol);
				}
			}
			
			fwrite($fileExport, $tab . $tab . ");" . $eol);
			fwrite($fileExport, $tab . "}" . $eol);
			
			fwrite($fileExport, "}" . $eol);
			
			fclose($fileExport);
		} catch(Exception $e) {
			fwrite($logExport, $lblErr . $e->getMessage() . $eol);
		}
	} catch(Exception $e) {
		fwrite($logExport, $lblErr . 'No definitions found' . $eol);
	}
	fwrite($logExport, $lblOk . 'Finished processing ' . $key . $eol . $eol);


	// Custom Fields --------------------------------------------------------------------------------------------------
	$key = 'Custom Field Group';
	fwrite($logExport, $lblOk . 'Start processing ' . $key . $eol);
	try{
		$def = $required[$key];
		
		try {
			$fileName = 'generic.customfield.def.inc.php';
			$fileExport = fopen($baseDir . $fileName, 'w');
			
			fwrite($fileExport, "<?php" . $eol . $eol);
			fwrite($fileExport, "class Generic_CustomField_Def {" . $eol . $eol);
			fwrite($fileExport, $tab . "const DT_FORMAT_YMD = 'yy-mm-dd';" . $eol . $eol);
			fwrite($fileExport, $tab . "// definitions for: " . $key . $eol . $eol);
			fwrite($fileExport, $tab . "static function required() {" . $eol);
			fwrite($fileExport, $tab . $tab . "return array(" .$eol);
			
			foreach($def as $elm) {
				try {
					fwrite($logExport, $lblOk . "Processing " . $elm . $eol);
					$sql = 'select * from ' . $db . '.civicrm_custom_group where title=\'' . $elm . '\'';
					try{
						$dao = CRM_Core_DAO::executeQuery($sql);
						$dao->fetch();
					} catch(Exception $e) {
						throw new Exception("Query for " . $elm . " failed");
					}
					if ($dao->N == 0) {;
						fwrite($logExport, $lblErr . "Query for " . $elm . " did not deliver any results!" . $eol);
					} else {
						fwrite($fileExport, $tab . $tab . $tab . "array(" . $eol);
						
						fwrite($fileExport, $tab . $tab . $tab . $tab . "'group_name' => '" . $dao->name . "'," . $eol);
						fwrite($fileExport, $tab . $tab . $tab . $tab . "'group_title' => '" . addslashes($dao->title) . "'," . $eol);
						fwrite($fileExport, $tab . $tab . $tab . $tab . "'extends' => array('" . $dao->extends . "')," . $eol);
						// entities in the array provide the data for custom group column 'extends_entity_column_value'
						fwrite($fileExport, $tab . $tab . $tab . $tab . "'entities' => array(" . $eol);
						if (is_null($dao->extends_entity_column_value)) {
							fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . "NULL," . $eol);
						} else {
							$typ = explode(chr(1), $dao->extends_entity_column_value);
							foreach($typ as $typ_key=>$typ_value) {
								if (!empty($typ_value)) {
									if (is_numeric($typ_value)) {
										if (array_key_exists($typ_value, $entities[$dao->extends])) {
											fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . "'" . $entities[$dao->extends][$typ_value] . "'," . $eol);
										} else {
											fwrite($logExport, $lblErr . 'Cannot translate entity column value ' . $typ_value . ' for custom group ' . $dao->title . ' to a title' . $eol);
										}
									} else {
										fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . "'" . $typ_value . "'," . $eol);
									}
								}
							}
						}
						fwrite($fileExport, $tab . $tab . $tab . $tab . ")," . $eol); // entities end
						fwrite($fileExport, $tab . $tab . $tab . $tab . "'style' => '" . $dao->style . "'," . $eol);
						fwrite($fileExport, $tab . $tab . $tab . $tab . "'is_multiple' => " . ($dao->is_multiple==0?"FALSE":"TRUE") . "," . $eol);
						fwrite($fileExport, $tab . $tab . $tab . $tab . "'help_pre' => '" . addslashes($dao->help_pre) . "'," . $eol);
						fwrite($fileExport, $tab . $tab . $tab . $tab . "'help_post' => '" . addslashes($dao->help_post) . "'," . $eol);
						fwrite($fileExport, $tab . $tab . $tab . $tab . "'collapse_display' => " . $dao->collapse_display . "," . $eol);
						fwrite($fileExport, $tab . $tab . $tab . $tab . "'collapse_adv_display' => " . $dao->collapse_adv_display . "," . $eol);
						fwrite($fileExport, $tab . $tab . $tab . $tab . "'fieldset' => array(" . $eol);
						// start loop custom fields
						$sql_fld = '
SELECT
  fld.*,
  @rn := @rn + 10 rank
FROM
  (SELECT
     cfld.*
   FROM
     ' . $db . '.civicrm_custom_group cgrp,
     ' . $db . '.civicrm_custom_field cfld
   WHERE
     cgrp.title = \'' . $elm . '\' AND
     cfld.custom_group_id = cgrp.id
   ORDER BY
     cfld.weight) fld,
  (SELECT @rn := 0) rnknr
						';
						try{
							$dao_fld = CRM_Core_DAO::executeQuery($sql_fld);
							if ($dao_fld->N == 0) {
								fwrite($logExport, $lblErr . "Query for custom fields in " . $elm . " did not deliver any results!" . $eol);
							} else {
								while($dao_fld->fetch()) {
									fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . "array(" . $eol);
									fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . $tab . "'name' => '" . $dao_fld->name . "'," . $eol);
									fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . $tab . "'label' => '" . addslashes($dao_fld->label) . "'," . $eol);
									fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . $tab . "'data_type' => '" . $dao_fld->data_type . "'," . $eol);
									fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . $tab . "'html_type' => '" . $dao_fld->html_type . "'," . $eol);
									if (is_null($dao_fld->default_value)) {
										fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . $tab . "'default_value' => NULL," . $eol);
									} else {
										fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . $tab . "'default_value' => '" . addslashes($dao_fld->default_value) . "'," . $eol);
									}
									$tmp = $dao_fld->is_required==1?'TRUE':'FALSE';
									fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . $tab . "'is_required' => " . $tmp . "," . $eol); // API: boolean
									$tmp = $dao_fld->is_searchable==1?'TRUE':'FALSE';
									fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . $tab . "'is_searchable' => " . $tmp . "," . $eol); // API: boolean
									$tmp = $dao_fld->is_search_range==1?'TRUE':'FALSE';
									fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . $tab . "'is_search_range' => " . $tmp . "," . $eol); // API: boolean
									fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . $tab . "'weight' => " . $dao_fld->rank . "," . $eol);
									if (is_null($dao_fld->help_pre)) {
										fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . $tab . "'help_pre' => NULL," . $eol);
									} else {
										fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . $tab . "'help_pre' => '" . addslashes($dao_fld->help_pre) . "'," . $eol);
									}
									if (is_null($dao_fld->help_post)) {
										fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . $tab . "'help_post' => NULL," . $eol);
									} else {
										fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . $tab . "'help_post' => '" . addslashes($dao_fld->help_post) . "'," . $eol);
									}
									if (is_null($dao_fld->attributes)) {
										fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . $tab . "'attributes' => NULL," . $eol);
									} else {
										fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . $tab . "'attributes' => '" . addslashes($dao_fld->attributes) . "'," . $eol);
									}
									if (is_null($dao_fld->options_per_line)) {
										fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . $tab . "'options_per_line' => NULL," . $eol);
									} else {
										fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . $tab . "'options_per_line' => " . $dao_fld->options_per_line . "," . $eol);
									}
									if (is_null($dao_fld->text_length)) {
										fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . $tab . "'text_length' => NULL," . $eol);
									} else {
										fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . $tab . "'text_length' => " . $dao_fld->text_length . "," . $eol);
									}
									if (is_null($dao_fld->start_date_years)) {
										fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . $tab . "'start_date_years' => NULL," . $eol);
									} else {
										fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . $tab . "'start_date_years' => " . $dao_fld->start_date_years . "," . $eol);
									}
									if (is_null($dao_fld->end_date_years)) {
										fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . $tab . "'end_date_years' => NULL," . $eol);
									} else {
										fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . $tab . "'end_date_years' => " . $dao_fld->end_date_years . "," . $eol);
									}
									if ($dao_fld->data_type!='Date') {
										fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . $tab . "'date_format' => NULL," . $eol); // API: string
									} else {
										fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . $tab . "'date_format' => self::DT_FORMAT_YMD," . $eol); // API: string
									}
									if (is_null($dao_fld->time_format)) {
										fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . $tab . "'time_format' => NULL," . $eol); // API: integer
									} else {
										fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . $tab . "'time_format' => $dao_fld->time_format," . $eol); // API: integer
									}
									if (is_null($dao_fld->note_columns)) {
										fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . $tab . "'note_columns' => NULL," . $eol);
									} else {
										fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . $tab . "'note_columns' => " . $dao_fld->note_columns . "," . $eol);
									}
									if (is_null($dao_fld->note_rows)) {
										fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . $tab . "'note_rows' => NULL," . $eol);
									} else {
										fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . $tab . "'note_rows' => " . $dao_fld->note_rows . "," . $eol);
									}
									if (is_null($dao_fld->option_group_id)) {
										fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . $tab . "'option_group_name' => NULL," . $eol);
									} elseif (array_key_exists($dao_fld->option_group_id, $entities['Option Group'])) {
										fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . $tab . "'option_group_name' => '" . $entities['Option Group'][$dao_fld->option_group_id] . "'," . $eol);
									} else {
										//error
										fwrite($logExport, $lblErr . 'Could not find a name for option group ' . $dao_fld->option_group_id . ' (field ' . $dao_fld->name . ')'. $eol);
									}
									fwrite($fileExport, $tab . $tab . $tab . $tab . $tab . ")," . $eol);
								}
							}
						} catch(Exception $e) {
							throw new Exception("Error processing custom fields for " . $elm . ": " . $e->getMessage());
						}
						// end loop custom fields
						
						fwrite($fileExport, $tab . $tab . $tab . $tab . ")," . $eol);
						
						fwrite($fileExport, $tab . $tab . $tab . ")," . $eol);
					}
				} catch(Exception $e) {
					fwrite($logExport, $lblErr . $e->getMessage() . $eol);
				}
			}
			
			fwrite($fileExport, $tab . $tab . ");" . $eol);
			fwrite($fileExport, $tab . "}" . $eol);
			
			fwrite($fileExport, "}" . $eol);
			
			fclose($fileExport);
		} catch(Exception $e) {
			fwrite($logExport, $lblErr . $e->getMessage() . $eol);
		}
	} catch(Exception $e) {
		fwrite($logExport, $lblErr . 'No definitions found' . $eol);
	}
	fwrite($logExport, $lblOk . 'Finished processing ' . $key . $eol . $eol);


	// Tags -----------------------------------------------------------------------------------------------------------
	$key = 'Tag';
	fwrite($logExport, $lblOk . 'Start processing ' . $key . $eol);
	try{
		$def_tmp = $required[$key];
		$def = array();
		// sort definitions by their parent id first
		$sql_def = 'SELECT * FROM ' . $db . '.civicrm_tag WHERE name IN (' . $this->_strArForSql($def_tmp) . ') ORDER BY ifnull(parent_id, 0), id';
		
		try{
			$dao_def = CRM_Core_DAO::executeQuery($sql_def);
			while ($dao_def->fetch()) {
				$def[] = $dao_def->name;
			}
		} catch(Exception $e) {
			throw new Exception("Failed to apply correct parent/child order!");
		}
		
		// report the requested elements that got lost in the query for sorting
		$def_lost = array_udiff($def_tmp, $def, 'strcasecmp');
		foreach($def_lost as $elm) {
			fwrite($logExport, $lblErr . "Query for " . $elm . " did not deliver any results!" . $eol);
		}
		
		try {
			$fileName = 'generic.tag.def.inc.php';
			$fileExport = fopen($baseDir . $fileName, 'w');
			
			fwrite($fileExport, "<?php" . $eol . $eol);
			fwrite($fileExport, "class Generic_Tag_Def {" . $eol . $eol);
			fwrite($fileExport, $tab . "// definitions for: " . $key . $eol . $eol);
			fwrite($fileExport, $tab . "static function required() {" . $eol);
			fwrite($fileExport, $tab . $tab . "return array(" .$eol);
			
			foreach($def as $elm) {
				try {
					fwrite($logExport, $lblOk . "Processing " . $elm . $eol);
					$sql = 'SELECT * FROM ' . $db . '.civicrm_tag WHERE name=\'' . $elm . '\'';
					try{
						$dao = CRM_Core_DAO::executeQuery($sql);
						$dao->fetch();
					} catch(Exception $e) {
						throw new Exception("Query for " . $elm . " failed");
					}
					if ($dao->N == 0) {;
						fwrite($logExport, $lblErr . "Query for " . $elm . " did not deliver any results!" . $eol);
					} else {
						fwrite($fileExport, $tab . $tab . $tab . "array(" . $eol);
						
						fwrite($fileExport, $tab . $tab . $tab . $tab . "'name' => '" . addslashes($dao->name) . "'," . $eol);
						fwrite($fileExport, $tab . $tab . $tab . $tab . "'description' => '" . addslashes($dao->description) . "'," . $eol);
						if (is_null($dao->parent_id)) {
							fwrite($fileExport, $tab . $tab . $tab . $tab . "'parent_tag' => NULL," . $eol);
						} elseif(array_key_exists($dao->parent_id, $entities['Tag'])) {
							if (array_search($entities['Tag'][$dao->parent_id], $def)===FALSE) {
								fwrite($logExport, $lblErr . 'Warning: parent ' . $entities['Tag'][$dao->parent_id] . ' for tag ' . $dao->name . ' does not exist in the definitions list - creation may fail in generic extension' . $eol);
							}
							fwrite($fileExport, $tab . $tab . $tab . $tab . "'parent_tag' => '" . addslashes($entities['Tag'][$dao->parent_id]) . "'," . $eol);
						} else {
							fwrite($logExport, $lblErr . 'Cannot translate tag parent id ' . $dao->parent_id . ' for tag ' . $dao->name . ' to a name' . $eol);
						}
						fwrite($fileExport, $tab . $tab . $tab . $tab . "'used_for' => '" . addslashes($dao->used_for) . "'," . $eol);

						fwrite($fileExport, $tab . $tab . $tab . ")," . $eol);

					}
				} catch(Exception $e) {
					fwrite($logExport, $lblErr . $e->getMessage() . $eol);
				}
			}
			
			fwrite($fileExport, $tab . $tab . ");" . $eol);
			fwrite($fileExport, $tab . "}" . $eol);
			
			fwrite($fileExport, "}" . $eol);
			
			fclose($fileExport);
		} catch(Exception $e) {
			fwrite($logExport, $lblErr . $e->getMessage() . $eol);
		}
	} catch(Exception $e) {
		fwrite($logExport, $lblErr . 'No definitions found' . $eol);
	}
	fwrite($logExport, $lblOk . 'Finished processing ' . $key . $eol . $eol);
	
	
	// Activity Types -------------------------------------------------------------------------------------------------
	$key = 'Activity Type';
	fwrite($logExport, $lblOk . 'Start processing ' . $key . $eol);
		try{
		$def = $required[$key];
		
		try {
			$fileName = 'generic.activitytype.def.inc.php';
			$fileExport = fopen($baseDir . $fileName, 'w');
			
			fwrite($fileExport, "<?php" . $eol . $eol);
			fwrite($fileExport, "class Generic_ActivityType_Def {" . $eol . $eol);
			fwrite($fileExport, $tab . "// definitions for: " . $key . $eol . $eol);
			fwrite($fileExport, $tab . "static function required() {" . $eol);
			fwrite($fileExport, $tab . $tab . "return array(" .$eol);
			
			foreach($def as $elm) {
				try {
					fwrite($logExport, $lblOk . "Processing " . $elm . $eol);
					$sql = 'select * from ' . $db . '.civicrm_contact_type where label=\'' . $elm . '\'';
					$sql = '
SELECT
  ogv.*
FROM
  ' . $db . '.civicrm_option_value ogv,
  ' . $db . '.civicrm_option_group ogp
WHERE
  ogv.option_group_id = ogp.id AND
  ogp.name = \'activity_type\' AND
  ogv.name = \'' . $elm . '\'
					';
					try{
						$dao = CRM_Core_DAO::executeQuery($sql);
						$dao->fetch();
					} catch(Exception $e) {
						throw new Exception("Query for " . $elm . " failed");
					}
					if ($dao->N == 0) {;
						fwrite($logExport, $lblErr . "Query for " . $elm . " did not deliver any results!" . $eol);
					} else {
						fwrite($fileExport, $tab . $tab . $tab . "array(" . $eol);

						fwrite($fileExport, $tab . $tab . $tab . $tab . "'name' => '" . $dao->name . "'," . $eol);
						fwrite($fileExport, $tab . $tab . $tab . $tab . "'label' => '" . addslashes($dao->label) . "'," . $eol);
						if (is_null($dao->component_id)) {
							fwrite($fileExport, $tab . $tab . $tab . $tab . "'component' => NULL," . $eol);
						} elseif (array_key_exists($dao->component_id, $entities['Component'])) {
							fwrite($fileExport, $tab . $tab . $tab . $tab . "'component' => '" . $entities['Component'][$dao->component_id] . "'," . $eol);
						} else {
							fwrite($logExport, $lblErr . 'Cannot translate component id ' . $dao->component_id . ' for activity type ' . $dao->name . ' to a name' . $eol);
						}
						fwrite($fileExport, $tab . $tab . $tab . $tab . "'description' => '" . addslashes($dao->description) . "'," . $eol);
						
						fwrite($fileExport, $tab . $tab . $tab . ")," . $eol);
					}
				} catch(Exception $e) {
					fwrite($logExport, $lblErr . $e->getMessage() . $eol);
				}
			}
			
			fwrite($fileExport, $tab . $tab . ");" . $eol);
			fwrite($fileExport, $tab . "}" . $eol);
			
			fwrite($fileExport, "}" . $eol);
			
			fclose($fileExport);
		} catch(Exception $e) {
			fwrite($logExport, $lblErr . $e->getMessage() . $eol);
		}
	} catch(Exception $e) {
		fwrite($logExport, $lblErr . 'No definitions found' . $eol);
	}
	fwrite($logExport, $lblOk . 'Finished processing ' . $key . $eol . $eol);

	
	// Roundup --------------------------------------------------------------------------------------------------------

	fclose($logExport);
	
    parent::run();
  }
  
  function _strArForSql($ar) {
	foreach($ar as $key=>$value) {
		$ar[$key] = '\'' . $value . '\'';
	}
	$result = implode(',', $ar);
	return $result;
  }
}
