<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Bootstrap</title>
    
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-primary">Test Bootstrap</h1>
        
        <!-- Test Button -->
        <button class="btn btn-success">Success Button</button>
        <button class="btn btn-danger">Danger Button</button>
        
        <!-- Test Alert -->
        <div class="alert alert-warning mt-3" role="alert">
            Ini alert dari Bootstrap!
        </div>
        
        <!-- Test Card -->
        <div class="card mt-3" style="width: 18rem;">
            <div class="card-body">
                <h5 class="card-title">Card Title</h5>
                <p class="card-text">Kalau card ini keliatan bagus, berarti Bootstrap sudah jalan!</p>
                <a href="#" class="btn btn-primary">Go somewhere</a>
            </div>
        </div>
        
        <!-- Test Modal -->
        <button type="button" class="btn btn-info mt-3" data-bs-toggle="modal" data-bs-target="#exampleModal">
            Launch Modal (Test JS)
        </button>
        
        <div class="modal fade" id="exampleModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Modal Title</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        Kalau modal ini bisa dibuka, berarti Bootstrap JS juga sudah jalan!
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
