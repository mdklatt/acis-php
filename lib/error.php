<?php
/* Exception classes for the ACIS library.

*/


class ACIS_ParameterError extends Exception
{
    /* The request parameters are invalid.

    */
}


class ACIS_RequestError extends Exception
{
    /* The server reported that the request was invalid.

    The ACIS server returned an HTTP status code of 400 indicating that it
    could not complete the request due to an invalid params object.

    */
}

class ACIS_ResultError extends Exception
{
    /* An error was reported by the ACIS result object.

    The server returned a result object, but it is invalid. The object contains
    an 'error' key with a string describing the error.

    */
}
