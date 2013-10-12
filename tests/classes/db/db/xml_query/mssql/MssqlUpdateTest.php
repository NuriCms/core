<?php

class MssqlUpdateTest extends MssqlTest {

	function _test($xml_file, $argsString, $expected, $expectedArgs = NULL) {
		$this->_testPreparedQuery($xml_file, $argsString, $expected, 'getUpdateSql', $expectedArgs = NULL);
	}

	function test_counter_updateCounterUnique() {
		$xml_file = _XE_PATH_ . "modules/counter/queries/updateCounterUnique.xml";
		$argsString = '$args->regdate = 25;';
		$expected = 'UPDATE [counter_status]
						SET [unique_visitor] = [unique_visitor] + ?
							, [pageview] = [pageview] + ?
						FROM [xe_counter_status]  as [counter_status]
						WHERE  [regdate] = ?';
		$this->_test($xml_file, $argsString, $expected, array("25", 1, 1));
	}

}

?>