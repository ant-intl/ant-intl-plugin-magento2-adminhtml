<?php

namespace Antom\Adminhtml\Controller\Adminhtml\Api;


class AlipayPayConsultResponse
{


    public $resultCode;
    public $resultMessage;
    public $resultStatus;

    /**
     * @return mixed
     */
    public function getResultCode() {
        return $this->resultCode;
    }


    /**
     * @param $resultCode
     * @return void
     */
    public function setResultCode($resultCode) {
        $this->resultCode = $resultCode;
    }

    /**
     * @return mixed
     */
    public function getResultMessage() {
        return $this->resultMessage;
    }

    /**
     * @param $resultMessage
     * @return void
     */
    public function setResultMessage($resultMessage) {
        $this->resultMessage = $resultMessage;
    }

    /**
     * @return mixed
     */
    public function getResultStatus() {
        return $this->resultStatus;
    }

    /**
     * @param $resultStatus
     * @return void
     */
    public function setResultStatus($resultStatus) {
        $this->resultStatus = $resultStatus;
    }

    public static function fromResponse(\stdClass $data): self {
        $obj = new self();
        $obj->resultCode   = $data->result->resultCode   ?? null;
        $obj->resultMessage = $data->result->resultMessage ?? null;
        $obj->resultStatus  = $data->result->resultStatus  ?? null;
        return $obj;
    }

    // Optional: stringify for debugging
    public function __toString(): string
    {
        return sprintf(
            "Code: %s | Message: %s | Status: %s",
            $this->resultCode,
            $this->resultMessage,
            $this->resultStatus
        );
    }
}
