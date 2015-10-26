<?php

function edr_alias_deprecated() {
	class_alias( 'Edr_Form', 'IB_Educator_Form' );
	class_alias( 'Edr_Tax_Manager', 'IB_Educator_Tax' );
	class_alias( 'Edr_Countries', 'IB_Educator_Countries' );
	class_alias( 'Edr_Request_Dispatcher', 'IB_Educator_Request' );
	class_alias( 'Edr_Student_Account', 'IB_Educator_Account' );
	class_alias( 'Edr_Post_Types', 'IB_Educator_Post_Types' );
	class_alias( 'Edr_Install', 'IB_Educator_Install' );
	class_alias( 'Edr_Email_Agent', 'IB_Educator_Email' );
	class_alias( 'Edr_Memberships', 'IB_Educator_Memberships' );
	class_alias( 'Edr_Memberships_Run', 'IB_Educator_Memberships_Run' );
	class_alias( 'Edr_View', 'IB_Educator_View' );
	class_alias( 'Edr_Payment_Gateway', 'IB_Educator_Payment_Gateway' );
}
