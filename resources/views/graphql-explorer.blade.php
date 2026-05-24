<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GraphQL Explorer</title>
    <style>body { margin: 0; height: 100vh; display: flex; flex-direction: column; }</style>
</head>
<body>
    {{-- Embedded GraphiQL via CDN — replace with a proper Vite build when ready --}}
    <div id="graphiql" style="height:100vh"></div>
    <link rel="stylesheet" href="https://unpkg.com/graphiql/graphiql.min.css" />
    <script src="https://unpkg.com/react/umd/react.production.min.js"></script>
    <script src="https://unpkg.com/react-dom/umd/react-dom.production.min.js"></script>
    <script src="https://unpkg.com/graphiql/graphiql.min.js"></script>
    <script>
        ReactDOM.render(
            React.createElement(GraphiQL, {
                fetcher: GraphiQL.createFetcher({
                    url: '/api/graphql',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                }),
            }),
            document.getElementById('graphiql'),
        );
    </script>
</body>
</html>
