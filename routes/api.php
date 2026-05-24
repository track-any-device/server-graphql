<?php

// The /graphql endpoint is handled entirely by Lighthouse.
// It is registered via Lighthouse's service provider using the route
// configured in config/lighthouse.php (middleware includes graphql.key
// and auth:sanctum so both machine-to-machine callers and browser
// sessions are accepted).
//
// No stub routes needed here — Lighthouse auto-registers /graphql.
