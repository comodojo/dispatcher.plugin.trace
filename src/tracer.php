<?php namespace Comodojo\DispatcherPlugin;

/**
 * A plugin to trace request/route/response to file
 * 
 * @package     Comodojo dispatcher (Spare Parts)
 * @author      comodojo <info@comodojo.org>
 * @license     GPL-3.0+
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

class Tracer {

    private $content = '';

    private $service = "default";

    private $can_trace = true;

    private $should_trace = true;

    private $should_trace_everything = false;

    private $traces_path = false;

    private $logger = false;

    public function __construct($dispatcherInstance) {

        $this->logger = $dispatcherInstance->getLogger();

        $current_time = $dispatcherInstance->getCurrentTime();

        $this->should_trace_everything = defined('DISPATCHER_TRACES_EVERYTHING') ? filter_var(DISPATCHER_TRACES_EVERYTHING, FILTER_VALIDATE_BOOLEAN) ? false;

        $this->traces_path = DISPATCHER_REAL_PATH."traces/";

        if ( !is_writable($this->traces_path) ) {

            $this->logger->error('Traces path not writeable, shutting down tracer');

            $this->can_trace = false;

        } else {

            $this->logger->info('Tracer online, request time: '.$this->current_time);

            $this->content .= "*************************\n";
            $this->content .= "****** TRACE START ******\n";

            $this->content .= " >> REQUEST FROM " . $_SERVER["REMOTE_ADDR"] . " AT " . $current_time . " <<\n";

        }

    }

    public function __destruct() {

        if ( !$this->can_trace ) return;

        $this->content .= "******* TRACE END *******\n";
        $this->content .= "*************************\n\n";

        if ( $this->should_trace || $this->should_trace_everything ) $this->writeTrace();

    }

    public function traceRequest($ObjectRequest) {

        if ( !$this->can_trace ) return;

        $this->logger->debug('Tracing request');

        $service = $ObjectRequest->getService();

        $this->service = empty($service) ? $this->service : $service;

        $this->content .= "------ REQUEST ------\n";

        $this->content .= "- Client requested service: " . $service . "\n";

        $this->content .= "- Client provided attributes: \n" . var_export($ObjectRequest->getAttributes(), true) . "\n";

        $this->content .= "- Client request's method: " . $ObjectRequest->getMethod() . "\n";

        $this->content .= "- Client provided parameters: \n" . var_export($ObjectRequest->getParameters(), true) . "\n";

        $this->content .= "- Request headers: \n" . var_export($ObjectRequest->getHeaders(), true) . "\n";

    }

    public function traceRoute($ObjectRoute) {

        if ( !$this->can_trace ) return;

        if ( $ObjectRoute->getParameter("trace") || $this->should_trace_everything ) {

            $this->logger->debug('Tracing route');

            $this->content .= "****** ROUTE ******\n";

            $this->content .= "* Server route source:" . $ObjectRoute->getService() . "\n";

            $this->content .= "* Server route target::class:" . $ObjectRoute->getTarget() . "::" . $ObjectRoute->getClass() . "\n";

            $this->content .= "* Server route type:" . $ObjectRoute->getType() . "\n";

            $this->content .= "* Server cache method:" . $ObjectRoute->getCache() . "\n";

            $this->should_trace = true;

        }

        else {

            $this->logger->debug('Tracing disabled for current service, discarding current trace');

            $this->should_trace = false;

        }

    }

    public function traceResult($ObjectResult) {

        if ( !$this->can_trace ) return;

        if ( $this->should_trace OR $this->should_trace_everything ) {

            $this->logger->debug('Tracing result');

            $this->content .= "++++++ RESULT ++++++\n";

            $this->content .= "+ Result status code:" . $ObjectResult->getStatusCode() . "\n";

            $this->content .= "+ Result location (if any):" . $ObjectResult->getLocation() . "\n";

            $this->content .= "+ Result content type:" . $ObjectResult->getContentType() . "\n";

            $this->content .= "+ Result charser:" . $ObjectResult->getCharset() . "\n";

            $this->content .= "+ Result headers: \n" . var_export($ObjectResult->getHeaders(),true) . "\n";

            $this->content .= "+ Result status code: \n" . $ObjectResult->getContent() . "\n";

        }

    }

    private function writeTrace() {

        $file = $this->traces_path.$this->service.".trace";

        $writedown = file_put_contents($file, $this->content, FILE_APPEND);

        if ( $writedown === false ) $this->logger->error('Could not write log file', array('LOGFILE' => $file));

    }

}

$t = new Tracer($dispatcher);

$dispatcher->addHook("dispatcher.request", $t, "traceRequest");

$dispatcher->addHook("dispatcher.serviceroute.#", $t, "traceRoute");

$dispatcher->addHook("dispatcher.result.#", $t, "traceResult");