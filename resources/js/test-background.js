const canvas = document.getElementById('bg-canvas');

if (canvas) {
    const ctx = canvas.getContext('2d');
    let width, height;
    let particles = [];

    // Check if fullscreen or container-based
    const isFullscreen = canvas.classList.contains('fullscreen-bg');
    const container = isFullscreen ? window : canvas.parentElement;

    // Colors: Soft Yellow and Neutrals (Increased visibility)
    const colors = ['rgba(255, 193, 7, 0.6)', 'rgba(255, 213, 79, 0.7)', 'rgba(206, 212, 218, 0.6)', 'rgba(173, 181, 189, 0.5)'];

    function resize() {
        if (isFullscreen) {
            width = canvas.width = window.innerWidth;
            height = canvas.height = window.innerHeight;
        } else {
            width = canvas.width = container.offsetWidth;
            height = canvas.height = container.offsetHeight;
        }
    }

    class Particle {
        constructor() {
            this.reset();
            this.x = Math.random() * width;
            this.y = Math.random() * height;
        }

        reset() {
            this.x = Math.random() * width;
            this.y = Math.random() * height;
            this.vx = (Math.random() - 0.5) * 0.4; // Slightly faster than previous
            this.vy = (Math.random() - 0.5) * 0.4;
            this.size = Math.random() * 15 + 8; // Sizes (8-23px)
            this.color = colors[Math.floor(Math.random() * colors.length)];
            this.shape = Math.floor(Math.random() * 4); // 0: Circle, 1: Square, 2: Triangle, 3: Hexagon
            this.angle = Math.random() * Math.PI * 2;
            this.spin = (Math.random() - 0.5) * 0.015;
            this.opacity = Math.random() * 0.4 + 0.2; // Opacity (0.2 - 0.6) - Visible but transparent
        }

        update() {
            this.x += this.vx;
            this.y += this.vy;
            this.angle += this.spin;

            const bounds = 50;
            if (this.x < -bounds || this.x > width + bounds || this.y < -bounds || this.y > height + bounds) {
                // Wrap around logic
                if (this.x < -bounds) this.x = width + bounds;
                if (this.x > width + bounds) this.x = -bounds;
                if (this.y < -bounds) this.y = height + bounds;
                if (this.y > height + bounds) this.y = -bounds;
            }
        }

        draw() {
            ctx.save();
            ctx.translate(this.x, this.y);
            ctx.rotate(this.angle);
            ctx.globalAlpha = this.opacity;
            ctx.fillStyle = this.color;

            // Draw Shapes
            ctx.beginPath();
            if (this.shape === 0) { // Circle
                ctx.arc(0, 0, this.size / 2, 0, Math.PI * 2);
            } else if (this.shape === 1) { // Square
                ctx.rect(-this.size / 2, -this.size / 2, this.size, this.size);
            } else if (this.shape === 2) { // Triangle
                ctx.moveTo(0, -this.size / 2);
                ctx.lineTo(this.size / 2, this.size / 2);
                ctx.lineTo(-this.size / 2, this.size / 2);
            } else if (this.shape === 3) { // Hexagon
                for (let i = 0; i < 6; i++) {
                    ctx.lineTo(this.size / 2 * Math.cos(i * Math.PI / 3), this.size / 2 * Math.sin(i * Math.PI / 3));
                }
            }
            ctx.closePath();
            ctx.fill();
            ctx.restore();
        }
    }

    function init() {
        resize();
        particles = [];
        // Medium density
        const area = width * height;
        const particleCount = Math.floor(area / 18000);
        for (let i = 0; i < particleCount; i++) {
            particles.push(new Particle());
        }
    }

    function animate() {
        ctx.clearRect(0, 0, width, height);

        // Draw Connecting Lines
        ctx.lineWidth = 0.8;
        ctx.strokeStyle = 'rgba(180, 180, 180, 0.2)';

        for (let i = 0; i < particles.length; i++) {
            let p1 = particles[i];
            p1.update();
            p1.draw();

            // Connect nearby particles
            for (let j = i + 1; j < particles.length; j++) {
                let p2 = particles[j];
                let dx = p1.x - p2.x;
                let dy = p1.y - p2.y;
                let dist = Math.sqrt(dx * dx + dy * dy);

                if (dist < 140) {
                    ctx.beginPath();
                    ctx.moveTo(p1.x, p1.y);
                    ctx.lineTo(p2.x, p2.y);
                    ctx.stroke();
                }
            }
        }

        requestAnimationFrame(animate);
    }

    window.addEventListener('resize', () => {
        resize();
        init(); // Re-init on significant resize to adjust density
    });

    // Start animation only if reduced motion is not preferred
    const mediaQuery = window.matchMedia("(prefers-reduced-motion: reduce)");
    if (!mediaQuery || !mediaQuery.matches) {
        init();
        animate();
    }
}
