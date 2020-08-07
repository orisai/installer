<?php declare(strict_types = 1);

namespace Composer\Semver\Constraint;

use function class_alias;
use function class_exists;

// Compatibility with composer/semver v2 and v3
if (!class_exists(EmptyConstraint::class) && class_exists(MatchAllConstraint::class)) {
	class_alias(MatchAllConstraint::class, EmptyConstraint::class);
}
