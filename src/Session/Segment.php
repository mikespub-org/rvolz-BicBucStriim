<?php

/**
 * Created by IntelliJ IDEA.
 * User: rv
 * Date: 01.10.15
 * Time: 09:31
 */

namespace BicBucStriim\Session;

use Aura\Session_Interface\SegmentInterface;
use Aura\Session\Segment as AuraSessionSegment;

/**
 *
 * Segment that integrates Aura Auth and Session..
 *
 */
class Segment extends AuraSessionSegment implements SegmentInterface {}
