<?php

namespace CMF\Core;

class HttpException extends \Fuel\Core\HttpException
{
	const BAD_REQUEST = 400;
	const UNAUTHORIZED = 401;
	const PAYMENT_REQUIRED = 402;
	const FORBIDDEN = 403;
	const NOT_FOUND = 404;
	const METHOD_NOT_ALLOWED = 405;
	const NOT_ACCEPTABLE = 406;
	const PROXY_AUTHENTICATION_REQUIRED = 407;
	const REQUEST_TIMEOUT = 408;
	const CONFLICT = 409;
	const GONE = 410;
	const LENGTH_REQUIRED = 411;
	const PRECONDITION_FAILED = 412;
	const ENTITY_TOO_LARGE = 413;
	const URI_TOO_LONG = 414;
	const UNSUPPORTED_MEDIA_TYPE = 415;
	const RANGE_NOT_SATISFIABLE = 416;
	const EXPECTATION_FAILED = 417;
	const TEAPOT = 418;
	const UNPROCESSABLE_ENTITY = 422;
	const LOCKED = 423;
	const FAILED_DEPENDENCY = 424;
	const UPGRADE_REQUIRED = 426;
	const PRECONDITION_REQUIRED = 428;
	const TOO_MANY_REQUESTS = 428;
	const REQUEST_HEADER_FIELDS_TOO_LARGE = 431;
	const INTERNAL_SERVER_ERROR = 500;
	const NOT_IMPLEMENTED = 501;
	const BAD_GATEWAY = 502;
	const SERVICE_UNAVAILABLE = 503;
	const GATEWAY_TIMEOUT = 504;
	const HTTP_VERSION_NOT_SUPPORTED = 505;
	const VARIANT_ALSO_NEGOTIATES = 506;
	const INSUFFICIENT_STORAGE = 507;
	const LOOP_DETECTED = 508;
	const BANDWIDTH_LIMIT_EXCEEDED = 509;
	const NOT_EXTENDED = 510;
	const NETWORK_AUTHENTICATION_REQUIRED = 511;

	public function response()
	{
		return new \Response(
			\View::forge('errors/http', array(
				'code' => $this->getCode(),
				'status' => \Arr::get(\Response::$statuses, $this->getCode(), 'Internal Server Error'),
				'message' => $this->getMessage()
			)),
			$this->getCode()
		);
	}
}