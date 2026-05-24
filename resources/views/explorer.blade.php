<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Track Any Device — GraphQL Explorer</title>
    <link rel="stylesheet" href="https://unpkg.com/graphiql@3/graphiql.min.css" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { height: 100vh; display: flex; flex-direction: column; font-family: system-ui, sans-serif; }
        #header {
            display: flex; align-items: center; gap: 12px;
            padding: 10px 16px; background: #1a1a2e; color: #fff;
            font-size: 14px; font-weight: 600; flex-shrink: 0;
        }
        #header span { opacity: .55; font-weight: 400; }
        #graphiql { flex: 1; overflow: hidden; }
    </style>
</head>
<body>
    <div id="header">
        Track Any Device
        <span>/ GraphQL Explorer</span>
    </div>
    <div id="graphiql"></div>

    <script crossorigin src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
    <script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
    <script crossorigin src="https://unpkg.com/graphiql@3/graphiql.min.js"></script>

    <script>
        const root = ReactDOM.createRoot(document.getElementById('graphiql'));
        root.render(
            React.createElement(GraphiQL, {
                fetcher: GraphiQL.createFetcher({
                    url: '/graphql',
                    // Pre-fill auth headers so the explorer works out of the box.
                    // Override in the Headers tab for user-level Sanctum sessions.
                    headers: {
                        'Authorization': 'Bearer {{ env("GRAPHQL_KEY") }}',
                        'X-Api-Secret': '{{ env("GRAPHQL_SECRET") }}',
                    },
                }),
                defaultEditorToolsVisibility: true,
            })
        );
    </script>
</body>
</html>
