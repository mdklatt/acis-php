<?php
/**
 * Exception classes for the ACIS library.
 *
 * All exception classes should be defined in this file.
 *
 */


 /**
  * The base class for all exceptions.
  *
  */
class ACIS_Exception extends Exception {}


/**
 * An invalid request.
 *
 */
class ACIS_RequestException extends ACIS_Exception {}


/**
 * An invalid result.
 *
 */
class ACIS_ResultException extends Exception {}
