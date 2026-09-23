<?php

// [AI] Setup Guide feature fully removed (Phase A4 cleanup).
// Previously defined: checkSetupGuideRequirements, checkSetupGuideCacheKey,
// updateSetupGuideCacheKey, getSetupGuideSteps. All callers migrated/removed;
// the enclosing Admin "Shipping_Method" route no longer exists, so these
// helpers were dead code that threw RouteNotFoundException on the admin layout.