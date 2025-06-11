<?php

namespace Component\Designpix;

use Request;
use Exception;
use Framework\Debug\Exception\AlertOnlyException;
use Framework\Debug\Exception\AlertRedirectException;

class SmsAgree
{
    private $db;

    public function __construct()
    {
        $this->db = \App::load('DB');
    }

    public function getSmsAgreement($memNo)
    {
        $query = "SELECT smsFl FROM es_member WHERE memNo = ?";
        $arrBind = [];
        $this->db->bind_param_push($arrBind, 'i', $memNo);
        $res = $this->db->query_fetch($query, $arrBind)[0]['smsFl'];

        return $res;
    }

    /**
     * Update smsFl value in es_member table and log the change in es_memberHistory table.
     *
     * @param int $memberId Member ID
     * @param string $smsFl New smsFl value ('y' or 'n')
     * @param string $reason Reason for the change
     * @return bool True on success, false on failure
     */
    public function updateSmsAgreement($memNo, $smsFl)
    {
        if (!in_array($smsFl, ['y', 'n'])) {
            throw new Exception(__('잘못된 접근입니다.'));
        }

        $this->db->begin_tran();

        try {
            $logQuery = "INSERT INTO es_memberHistory (memNo, processor, processorIp, updateColumn, beforeValue, afterValue, otherValue, regDt)
                VALUES (%s, '%s', '%s', '%s', '%s', '%s', 'null', NOW())
            ";
            $processor = 'member';
            $processorIp = (Request::getRemoteAddress() ?? 'unknown');
            $updateColumn = 'SMS수신동의';

            // Fetch the current smsFl value before updating
            $beforeValueQuery = "SELECT smsFl FROM es_member WHERE memNo = %s";
            $beforeValueQuery = sprintf($beforeValueQuery, $memNo);
            $beforeValue = $this->db->fetch($beforeValueQuery)['smsFl'];

            $logQuery = sprintf($logQuery, $memNo, $processor, $processorIp, $updateColumn, $beforeValue, $smsFl);

            $res = $this->db->query($logQuery);
            if (!$res) throw new Exception('Insert log error');

            $updateQuery = "UPDATE es_member SET smsFl = '%s' WHERE memNo = %s";
            $updateQuery = sprintf($updateQuery, $smsFl, $memNo);

            $res = $this->db->query($updateQuery);
            if (!$res) throw new Exception('Update smsFl error');
            
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            return false;
        }
    }
}
