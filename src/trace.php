<?php namespace comodojo\DispatcherPlugin;

/**
 * A plugin to trace request/route/response to file
 * 
 * @package		Comodojo dispatcher (Spare Parts)
 * @author		comodojo <info@comodojo.org>
 * @license		GPL-3.0+
 *
 * LICENSE:
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

global $dispatcher;

define("DISPATCHER_TRACES_PATH", DISPATCHER_REAL_PATH."traces/");

define("DISPATCHER_TRACES_EVERYTHING", false);

class trace {

	private $content = '';

	private $service = "default";

	private $should_trace = false;

	public function __construct($time) {

		$current_time = $time;

		$this->content .= "*************************\n";
		$this->content .= "****** TRACE START ******\n";

		$this->content .= " >> REQUEST FROM " . $_SERVER["REMOTE_ADDR"] . " AT " . $current_time . " <<\n";

	}

	public function __destruct() {

		$this->content .= "******* TRACE END *******\n";
		$this->content .= "*************************\n\n";

		if ( $this->should_trace || DISPATCHER_TRACES_EVERYTHING ) $this->write_content();

	}

	public function trace_request($ObjectRequest) {

		\comodojo\Dispatcher\debug("Tracing request","INFO","trace");

		$service = $ObjectRequest->getService();

		$this->service = empty($service) ? $this->service : $service;

		$this->content .= "------ REQUEST ------\n";

		$this->content .= "- Client requested service: " . $service . "\n";

		$this->content .= "- Client provided attributes: \n" . var_export($ObjectRequest->getAttributes(), true) . "\n";

		$this->content .= "- Client request's method: " . $ObjectRequest->getMethod() . "\n";

		$this->content .= "- Client provided parameters: \n" . var_export($ObjectRequest->getParameters(), true) . "\n";

		$this->content .= "- Request headers: \n" . var_export($ObjectRequest->getHeaders(), true) . "\n";

	}

	public function trace_route($ObjectRoute) {

		if ( $ObjectRoute->getParameter("trace") || DISPATCHER_TRACES_EVERYTHING ) {

			\comodojo\Dispatcher\debug("Tracing route","INFO","trace");

			$this->content .= "****** ROUTE ******\n";

			$this->content .= "* Server route source:" . $ObjectRoute->getService() . "\n";

			$this->content .= "* Server route target::class:" . $ObjectRoute->getTarget() . "::" . $ObjectRoute->getClass() . "\n";

			$this->content .= "* Server route type:" . $ObjectRoute->getType() . "\n";

			$this->content .= "* Server cache method:" . $ObjectRoute->getCache() . "\n";

			$this->should_trace = true;

		}

		else {

			\comodojo\Dispatcher\debug("Tracing disabled for current service, discarding current trace","INFO","trace");

			$this->should_trace = false;

		}

	}

	public function trace_result($ObjectResult) {

		if ( $this->should_trace === true OR DISPATCHER_TRACES_EVERYTHING === true ) {

			\comodojo\Dispatcher\debug("Tracing result","INFO","trace");

			$this->content .= "++++++ RESULT ++++++\n";

			$this->content .= "+ Result status code:" . $ObjectResult->getStatusCode() . "\n";

			$this->content .= "+ Result location (if any):" . $ObjectResult->getLocation() . "\n";

			$this->content .= "+ Result content type:" . $ObjectResult->getContentType() . "\n";

			$this->content .= "+ Result charser:" . $ObjectResult->getCharset() . "\n";

			$this->content .= "+ Result headers: \n" . var_export($ObjectResult->getHeaders(),true) . "\n";

			$this->content .= "+ Result status code: \n" . $ObjectResult->getContent() . "\n";

		}

	}

	private function write_content() {

		$file = DISPATCHER_TRACES_PATH.$this->service.".trace";

		$writedown = file_put_contents($file, $this->content, FILE_APPEND);

		if ( $writedown === false ) \comodojo\Dispatcher\debug('Could not write log file!','ERROR','trace');

	}

}

$t = new trace($dispatcher->getCurrentTime());

$dispatcher->addHook("dispatcher.request", $t, "trace_request");

$dispatcher->addHook("dispatcher.serviceroute.#", $t, "trace_route");

$dispatcher->addHook("dispatcher.result.#", $t, "trace_result");

?>