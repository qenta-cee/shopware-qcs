<?php
/*
* Die vorliegende Software ist Eigentum von Wirecard CEE und daher vertraulich
* zu behandeln. Jegliche Weitergabe an dritte, in welcher Form auch immer, ist
* unzulaessig.
*
* Software & Service Copyright (C) by
* Wirecard Central Eastern Europe GmbH,
* FB-Nr: FN 195599 x, http://www.wirecard.at
*/
/**
 * @name WirecardCEE_QMore_Return_Failure
 * @category WirecardCEE
 * @package WirecardCEE_QMore
 * @subpackage Return
 * @version 3.1.0
 */
 class WirecardCEE_QMore_Return_Failure extends WirecardCEE_Stdlib_Return_Failure {

    /**
     * Returns the number of errors
     * @return int
     */
    public function getNumberOfErrors() {
        return (int) $this->__get(self::$ERRORS);
    }

    /**
     * Returns all the errors
     * return Array
     */
    public function getErrors() {
        if (empty($this->_errors)) {
            $errorList = Array();

            for ($i = 1; $i <= $this->getNumberOfErrors(); $i++)
            {
                $field = sprintf('%s_%d_', self::$ERROR, $i);

                $errorCode = $this->__get($field . self::$ERROR_ERROR_CODE);
                $message = $this->__get($field . self::$ERROR_MESSAGE);
                $consumerMessage = $this->__get($field . self::$ERROR_CONSUMER_MESSAGE);
                $paySysMessage = $this->__get($field . self::$ERROR_PAY_SYS_MESSAGE);

                $errorList[$i-1] = new WirecardCEE_QMore_Error($errorCode, $message);
                $errorList[$i-1]->setPaySysMessage($paySysMessage);
                $errorList[$i-1]->setConsumerMessage($consumerMessage);
            }

            $this->_errors = $errorList;
        }
        return $this->_errors;
    }
}