<?php
/**
 * IntroController.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Json;

use FireflyIII\Support\Facades\Preferences;
use Log;

/**
 * Class IntroController.
 */
class IntroController
{
    /**
     * Get the intro steps. There are currently no specific routes with an outro step.
     *
     * @param string $route
     * @param string $specificPage
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getIntroSteps(string $route, string $specificPage = '')
    {
        Log::debug(sprintf('getIntroSteps for route "%s" and page "%s"', $route, $specificPage));
        $steps         = $this->getBasicSteps($route);
        $specificSteps = $this->getSpecificSteps($route, $specificPage);
        if (0 === count($specificSteps)) {
            Log::debug(sprintf('No specific steps for route "%s" and page "%s"', $route, $specificPage));

            return response()->json($steps);
        }
        if ($this->hasOutroStep($route)) {
            // @codeCoverageIgnoreStart
            // save last step:
            $lastStep = $steps[count($steps) - 1];
            // remove last step:
            array_pop($steps);
            // merge arrays and add last step again
            $steps   = array_merge($steps, $specificSteps);
            $steps[] = $lastStep;
            // @codeCoverageIgnoreEnd
        }
        if (!$this->hasOutroStep($route)) {
            $steps = array_merge($steps, $specificSteps);
        }

        return response()->json($steps);
    }

    /**
     * @param string $route
     *
     * @return bool
     */
    public function hasOutroStep(string $route): bool
    {
        $routeKey = str_replace('.', '_', $route);
        Log::debug(sprintf('Has outro step for route %s', $routeKey));
        $elements = config(sprintf('intro.%s', $routeKey));
        if (!\is_array($elements)) {
            return false;
        }

        $hasStep = array_key_exists('outro', $elements);

        Log::debug('Elements is array', $elements);
        Log::debug('Keys is', array_keys($elements));
        Log::debug(sprintf('Keys has "outro": %s', var_export($hasStep, true)));

        return $hasStep;
    }

    /**
     * @param string $route
     * @param string $specialPage
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function postEnable(string $route, string $specialPage = '')
    {
        $route = str_replace('.', '_', $route);
        $key   = 'shown_demo_' . $route;
        if ('' !== $specialPage) {
            $key .= '_' . $specialPage;
        }
        Log::debug(sprintf('Going to mark the following route as NOT done: %s with special "%s" (%s)', $route, $specialPage, $key));
        Preferences::set($key, false);

        return response()->json(['message' => trans('firefly.intro_boxes_after_refresh')]);
    }

    /**
     * @param string $route
     * @param string $specialPage
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function postFinished(string $route, string $specialPage = '')
    {
        $key = 'shown_demo_' . $route;
        if ('' !== $specialPage) {
            $key .= '_' . $specialPage;
        }
        Log::debug(sprintf('Going to mark the following route as done: %s with special "%s" (%s)', $route, $specialPage, $key));
        Preferences::set($key, true);

        return response()->json(['result' => sprintf('Reported demo watched for route "%s".', $route)]);
    }

    /**
     * @param string $route
     *
     * @return array
     */
    private function getBasicSteps(string $route): array
    {
        $routeKey = str_replace('.', '_', $route);
        $elements = config(sprintf('intro.%s', $routeKey));
        $steps    = [];
        if (is_array($elements) && count($elements) > 0) {
            foreach ($elements as $key => $options) {
                $currentStep = $options;

                // get the text:
                $currentStep['intro'] = trans('intro.' . $route . '_' . $key);

                // save in array:
                $steps[] = $currentStep;
            }
        }
        Log::debug(sprintf('Total basic steps for %s is %d', $routeKey, count($steps)));

        return $steps;
    }

    /**
     * @param string $route
     * @param string $specificPage
     *
     * @return array
     */
    private function getSpecificSteps(string $route, string $specificPage): array
    {
        $steps    = [];
        $routeKey = '';

        // user is on page with specific instructions:
        if (strlen($specificPage) > 0) {
            $routeKey = str_replace('.', '_', $route);
            $elements = config(sprintf('intro.%s', $routeKey . '_' . $specificPage));
            if (is_array($elements) && count($elements) > 0) {
                foreach ($elements as $key => $options) {
                    $currentStep = $options;

                    // get the text:
                    $currentStep['intro'] = trans('intro.' . $route . '_' . $specificPage . '_' . $key);

                    // save in array:
                    $steps[] = $currentStep;
                }
            }
        }
        Log::debug(sprintf('Total specific steps for route "%s" and page "%s" (routeKey is "%s") is %d', $route, $specificPage, $routeKey, count($steps)));

        return $steps;
    }
}
