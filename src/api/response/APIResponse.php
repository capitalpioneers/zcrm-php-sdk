<?php

namespace ZCRM\api\response;

use ZCRM\common\APIConstants;
use ZCRM\exception\ZCRMException;
use ZCRM\exception\APIExceptionHandler;

class APIResponse extends CommonAPIResponse {

  private $data = NULL;

  private $status = NULL;

  public function __construct($httpResponse, $httpStatusCode, $apiName = NULL) {
    parent::__construct($httpResponse, $httpStatusCode, $apiName);
  }

  public function setData($data) {
    $this->data = $data;
  }

  public function getData() {
    return $this->data;
  }

  /**
   * Get the response status
   *
   * @return String
   */
  public function getStatus() {
    return $this->status;
  }

  /**
   * Set the response status
   *
   * @param String $status
   */
  public function setStatus($status) {
    $this->status = $status;
  }

  /**
   * @throws ZCRMException
   */
  public function handleForFaultyResponses() {
    $statusCode = self::getHttpStatusCode();
    if (in_array($statusCode, APIExceptionHandler::getFaultyResponseCodes())) {
      if ($statusCode == APIConstants::RESPONSECODE_NO_CONTENT) {
        $exception = new ZCRMException(APIConstants::INVALID_DATA . "-" . APIConstants::INVALID_ID_MSG, $statusCode);
        $exception->setExceptionCode("No Content");
        throw $exception;
      }
      else {
        $responseJSON = $this->getResponseJSON();
        $exception = new ZCRMException($responseJSON['message'], $statusCode);
        $exception->setExceptionCode($responseJSON['code']);
        $exception->setExceptionDetails($responseJSON['details']);
        throw $exception;
      }
    }
  }

  /**
   * @throws ZCRMException
   */
  public function processResponseData() {
    $responseJSON = $this->getResponseJSON();
    if ($responseJSON == NULL) {
      return;
    }
    if (array_key_exists("data", $responseJSON)) {
      $responseJSON = $responseJSON['data'][0];
    }
    else {
      if (array_key_exists("users", $responseJSON)) {
        $responseJSON = $responseJSON['users'][0];
      }
      else {
        if (array_key_exists("modules", $responseJSON)) {
          $responseJSON = $responseJSON['modules'];
        }
      }
    }
    if (isset($responseJSON['status']) && $responseJSON['status'] == APIConstants::STATUS_ERROR) {
      $exception = new ZCRMException($responseJSON['message'], self::getHttpStatusCode());
      $exception->setExceptionCode($responseJSON['code']);
      $exception->setExceptionDetails($responseJSON['details']);
      throw $exception;
    }
    elseif (isset($responseJSON['status']) && $responseJSON['status'] == APIConstants::STATUS_SUCCESS) {
      self::setCode($responseJSON['code']);
      self::setStatus($responseJSON['status']);
      self::setMessage($responseJSON['message']);
      self::setDetails($responseJSON['details']);
    }
  }

}

?>