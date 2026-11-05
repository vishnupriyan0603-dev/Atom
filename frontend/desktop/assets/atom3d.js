/**
 * ATOM AI Assistant - 3D WebGL Avatar & Visual FX Engine
 * Features: Interactive 3D Robot, Procedural Neural Cortex, Quantum Core,
 * 3D Mouse Tracking, Orbit Controls, Audio Spectrum Visualizer, and Particle FX.
 */

(function () {
    var engine = {
        mode: 'bot', // 'bot', 'brain', 'core'
        wireframe: false,
        container: null,
        canvas: null,
        scene: null,
        camera: null,
        renderer: null,
        robotGroup: null,
        brainGroup: null,
        coreGroup: null,
        particlesGroup: null,
        headMesh: null,
        leftEye: null,
        rightEye: null,
        reactorMesh: null,
        torusRings: [],
        mouse: { x: 0, y: 0, targetX: 0, targetY: 0 },
        isDragging: false,
        prevMouseX: 0,
        prevMouseY: 0,
        manualRotationY: 0,
        manualRotationX: 0,
        clock: 0,
        audioLevel: 0.2,
        isSpeaking: false
    };

    // Color Palette
    var PALETTE = {
        gunmetal: 0x1e2735,
        bronze: 0x8f7a5a,
        neonGreen: 0x22c55e,
        neonCyan: 0x38bdf8,
        darkBase: 0x080a0d,
        coreGlow: 0x22c55e,
        cortexLeft: 0x22c55e,
        cortexRight: 0x38bdf8
    };

    // Initialize 3D Viewport
    function init3D() {
        engine.container = document.getElementById('bot3dContainer');
        engine.canvas = document.getElementById('bot3dCanvas');
        if (!engine.container || !engine.canvas) return;

        // Fallback check for Three.js
        if (typeof THREE === 'undefined') {
            console.warn('Three.js not loaded. Initializing 2D procedural canvas avatar.');
            init2DFallback();
            return;
        }

        try {
            var width = engine.container.clientWidth || 300;
            var height = engine.container.clientHeight || 260;

            // Scene & Camera
            engine.scene = new THREE.Scene();
            engine.camera = new THREE.PerspectiveCamera(45, width / height, 0.1, 100);
            engine.camera.position.set(0, 0.8, 5.8);

            // Renderer with transparency & high pixel ratio
            engine.renderer = new THREE.WebGLRenderer({
                canvas: engine.canvas,
                antialias: true,
                alpha: true
            });
            engine.renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
            engine.renderer.setSize(width, height);

            // Lights
            var ambientLight = new THREE.AmbientLight(0xffffff, 0.65);
            engine.scene.add(ambientLight);

            var keyLight = new THREE.DirectionalLight(0xffffff, 0.85);
            keyLight.position.set(4, 6, 5);
            engine.scene.add(keyLight);

            var rimLight = new THREE.DirectionalLight(PALETTE.neonCyan, 0.7);
            rimLight.position.set(-5, 3, -3);
            engine.scene.add(rimLight);

            var coreLight = new THREE.PointLight(PALETTE.neonGreen, 1.2, 8);
            coreLight.position.set(0, 0.9, 0.6);
            engine.scene.add(coreLight);

            // Build 3D Models
            buildRobotModel();
            buildBrainModel();
            buildQuantumCoreModel();
            buildParticleAura();

            // Set Initial Active Mode
            setMode(engine.mode);

            // Event Listeners for Mouse Tracking and Orbit Dragging
            setupInteractions();

            // Start Render Loop
            renderLoop();
        } catch (e) {
            console.error('WebGL initialization error:', e);
            init2DFallback();
        }
    }

    // Material Helper
    function createMat(color, metalness, roughness, emissive, emissiveIntensity) {
        return new THREE.MeshStandardMaterial({
            color: color,
            metalness: metalness || 0.4,
            roughness: roughness || 0.6,
            emissive: emissive || 0x000000,
            emissiveIntensity: emissiveIntensity || 0,
            wireframe: engine.wireframe
        });
    }

    // 1. Build 3D ATOM Robot Model
    function buildRobotModel() {
        engine.robotGroup = new THREE.Group();

        var bodyMat = createMat(PALETTE.gunmetal, 0.7, 0.35);
        var trimMat = createMat(PALETTE.bronze, 0.6, 0.45);
        var darkMat = createMat(0x0c0f14, 0.2, 0.8);
        var greenGlowMat = createMat(PALETTE.neonGreen, 0.2, 0.3, PALETTE.neonGreen, 1.2);
        var cyanGlowMat = createMat(PALETTE.neonCyan, 0.2, 0.3, PALETTE.neonCyan, 1.5);

        // Torso / Chest
        var chest = new THREE.Mesh(new THREE.BoxGeometry(1.3, 0.95, 0.7), bodyMat);
        chest.position.y = 1.0;
        engine.robotGroup.add(chest);

        // Chest Armor Plate
        var chestPlate = new THREE.Mesh(new THREE.BoxGeometry(0.9, 0.65, 0.76), trimMat);
        chestPlate.position.set(0, 1.05, 0.02);
        engine.robotGroup.add(chestPlate);

        // Pulsing 3D Reactor Core
        var reactorGeo = new THREE.SphereGeometry(0.2, 24, 24);
        engine.reactorMesh = new THREE.Mesh(reactorGeo, greenGlowMat);
        engine.reactorMesh.position.set(0, 1.05, 0.4);
        engine.robotGroup.add(engine.reactorMesh);

        // Reactor Core Outer Ring
        var reactorRing = new THREE.Mesh(new THREE.TorusGeometry(0.26, 0.03, 12, 32), trimMat);
        reactorRing.position.set(0, 1.05, 0.4);
        engine.robotGroup.add(reactorRing);

        // Waist & Hip
        var waist = new THREE.Mesh(new THREE.CylinderGeometry(0.38, 0.45, 0.45, 12), darkMat);
        waist.position.y = 0.45;
        engine.robotGroup.add(waist);

        // Shoulders
        [-1, 1].forEach(function (side) {
            var pauldron = new THREE.Mesh(new THREE.BoxGeometry(0.46, 0.38, 0.52), trimMat);
            pauldron.position.set(side * 0.82, 1.35, 0);
            pauldron.rotation.z = side * 0.15;
            engine.robotGroup.add(pauldron);
        });

        // Head Group (Rotates dynamically with cursor)
        engine.headMesh = new THREE.Group();
        engine.headMesh.position.y = 1.85;

        var headHelmet = new THREE.Mesh(new THREE.SphereGeometry(0.44, 24, 24), bodyMat);
        headHelmet.scale.set(1, 1.05, 0.95);
        engine.headMesh.add(headHelmet);

        var visor = new THREE.Mesh(new THREE.BoxGeometry(0.74, 0.22, 0.6), darkMat);
        visor.position.set(0, 0.02, 0.22);
        engine.headMesh.add(visor);

        // Glowing Cyber Eyes
        var eyeGeo = new THREE.SphereGeometry(0.065, 16, 16);
        engine.leftEye = new THREE.Mesh(eyeGeo, cyanGlowMat);
        engine.leftEye.position.set(-0.16, 0.03, 0.46);
        engine.headMesh.add(engine.leftEye);

        engine.rightEye = new THREE.Mesh(eyeGeo, cyanGlowMat);
        engine.rightEye.position.set(0.16, 0.03, 0.46);
        engine.headMesh.add(engine.rightEye);

        // Antenna
        var antStem = new THREE.Mesh(new THREE.CylinderGeometry(0.02, 0.02, 0.3, 8), trimMat);
        antStem.position.set(0, 0.52, 0);
        engine.headMesh.add(antStem);

        var antDot = new THREE.Mesh(new THREE.SphereGeometry(0.06, 12, 12), greenGlowMat);
        antDot.position.set(0, 0.68, 0);
        engine.headMesh.add(antDot);

        engine.robotGroup.add(engine.headMesh);

        // Mechanical Arms
        function createArm(side) {
            var arm = new THREE.Group();
            var bicep = new THREE.Mesh(new THREE.CylinderGeometry(0.13, 0.11, 0.48, 10), bodyMat);
            bicep.position.set(side * 0.88, 1.0, 0);
            arm.add(bicep);

            var forearm = new THREE.Mesh(new THREE.CylinderGeometry(0.11, 0.09, 0.42, 10), trimMat);
            forearm.position.set(side * 0.88, 0.58, 0.08);
            forearm.rotation.x = 0.2;
            arm.add(forearm);

            var hand = new THREE.Mesh(new THREE.BoxGeometry(0.16, 0.18, 0.16), darkMat);
            hand.position.set(side * 0.88, 0.32, 0.14);
            arm.add(hand);

            return arm;
        }
        engine.robotGroup.add(createArm(-1));
        engine.robotGroup.add(createArm(1));

        // Floating Levitation Base / Holographic Rings
        var baseRing = new THREE.Mesh(new THREE.TorusGeometry(0.7, 0.03, 8, 36), greenGlowMat);
        baseRing.rotation.x = Math.PI / 2;
        baseRing.position.y = 0.05;
        engine.robotGroup.add(baseRing);

        engine.robotGroup.position.y = -0.75;
        engine.scene.add(engine.robotGroup);
    }

    // 2. Build 3D Procedural Neural Cortex Model
    function buildBrainModel() {
        engine.brainGroup = new THREE.Group();
        var pointCount = 1800;
        var positions = new Float32Array(pointCount * 3);
        var colors = new Float32Array(pointCount * 3);
        var color1 = new THREE.Color(PALETTE.cortexLeft);
        var color2 = new THREE.Color(PALETTE.cortexRight);
        var tmpColor = new THREE.Color();

        var R = 1.35;
        for (var i = 0; i < pointCount; i++) {
            var theta = 2 * Math.PI * Math.random();
            var phi = Math.acos(1 - 2 * Math.random());

            var x = Math.sin(phi) * Math.cos(theta);
            var y = Math.cos(phi);
            var z = Math.sin(phi) * Math.sin(theta);

            // Gyri & Sulci surface folds
            var fold = Math.sin(theta * 5 + phi * 4) * Math.cos(phi * 6 - theta * 3) * 0.14;
            var r = R * (1 + fold);
            x *= r;
            y *= r * 0.9;
            z *= r * 0.8;

            // Split hemispheres
            var gap = 0.12;
            x += (x >= 0 ? gap : -gap);

            positions[i * 3] = x;
            positions[i * 3 + 1] = y;
            positions[i * 3 + 2] = z;

            tmpColor.lerpColors(color1, color2, (x + 1.5) / 3.0);
            colors[i * 3] = tmpColor.r;
            colors[i * 3 + 1] = tmpColor.g;
            colors[i * 3 + 2] = tmpColor.b;
        }

        var geo = new THREE.BufferGeometry();
        geo.setAttribute('position', new THREE.BufferAttribute(positions, 3));
        geo.setAttribute('color', new THREE.BufferAttribute(colors, 3));

        var mat = new THREE.PointsMaterial({
            size: 0.055,
            vertexColors: true,
            transparent: true,
            opacity: 0.9,
            blending: THREE.AdditiveBlending
        });

        var brainPoints = new THREE.Points(geo, mat);
        engine.brainGroup.add(brainPoints);

        // Core glow sphere inside brain
        var brainCoreMat = createMat(PALETTE.neonGreen, 0.1, 0.2, PALETTE.neonGreen, 0.8);
        var brainCore = new THREE.Mesh(new THREE.SphereGeometry(0.4, 16, 16), brainCoreMat);
        engine.brainGroup.add(brainCore);

        engine.brainGroup.position.y = 0.7;
        engine.brainGroup.visible = false;
        engine.scene.add(engine.brainGroup);
    }

    // 3. Build 3D Quantum Core Model
    function buildQuantumCoreModel() {
        engine.coreGroup = new THREE.Group();

        var coreMat = createMat(PALETTE.neonGreen, 0.2, 0.1, PALETTE.neonGreen, 2.0);
        var coreSphere = new THREE.Mesh(new THREE.SphereGeometry(0.45, 32, 32), coreMat);
        engine.coreGroup.add(coreSphere);

        var ring1Mat = createMat(PALETTE.neonCyan, 0.2, 0.1, PALETTE.neonCyan, 1.4);
        var ring2Mat = createMat(PALETTE.bronze, 0.6, 0.3, PALETTE.neonGreen, 0.6);
        var ring3Mat = createMat(PALETTE.gunmetal, 0.8, 0.2, PALETTE.neonCyan, 0.8);

        var r1 = new THREE.Mesh(new THREE.TorusGeometry(1.0, 0.04, 16, 48), ring1Mat);
        var r2 = new THREE.Mesh(new THREE.TorusGeometry(1.25, 0.04, 16, 48), ring2Mat);
        var r3 = new THREE.Mesh(new THREE.TorusGeometry(1.5, 0.04, 16, 48), ring3Mat);

        engine.torusRings = [r1, r2, r3];
        engine.coreGroup.add(r1);
        engine.coreGroup.add(r2);
        engine.coreGroup.add(r3);

        engine.coreGroup.position.y = 0.7;
        engine.coreGroup.visible = false;
        engine.scene.add(engine.coreGroup);
    }

    // 4. Build Ambient Particle Aura
    function buildParticleAura() {
        engine.particlesGroup = new THREE.Group();
        var pCount = 200;
        var pGeo = new THREE.BufferGeometry();
        var pPos = new Float32Array(pCount * 3);

        for (var i = 0; i < pCount; i++) {
            pPos[i * 3] = (Math.random() - 0.5) * 5.0;
            pPos[i * 3 + 1] = Math.random() * 3.5 - 0.5;
            pPos[i * 3 + 2] = (Math.random() - 0.5) * 4.0;
        }

        pGeo.setAttribute('position', new THREE.BufferAttribute(pPos, 3));
        var pMat = new THREE.PointsMaterial({
            size: 0.035,
            color: PALETTE.neonGreen,
            transparent: true,
            opacity: 0.55,
            blending: THREE.AdditiveBlending
        });

        var aura = new THREE.Points(pGeo, pMat);
        engine.particlesGroup.add(aura);
        engine.scene.add(engine.particlesGroup);
    }

    // Mode Switcher
    function setMode(mode) {
        engine.mode = mode;
        if (engine.robotGroup) engine.robotGroup.visible = (mode === 'bot');
        if (engine.brainGroup) engine.brainGroup.visible = (mode === 'brain');
        if (engine.coreGroup) engine.coreGroup.visible = (mode === 'core');

        var modeBtns = document.querySelectorAll('.mode-btn');
        for (var i = 0; i < modeBtns.length; i++) {
            modeBtns[i].className = 'mode-btn';
            if (modeBtns[i].getAttribute('data-mode') === mode) {
                modeBtns[i].className = 'mode-btn active';
            }
        }
    }

    // Toggle Wireframe Mode
    function toggleWireframe() {
        engine.wireframe = !engine.wireframe;
        engine.scene.traverse(function (child) {
            if (child.material && typeof child.material.wireframe !== 'undefined') {
                child.material.wireframe = engine.wireframe;
            }
        });
    }

    // Reset 3D Camera & Rotation
    function resetCamera() {
        engine.manualRotationX = 0;
        engine.manualRotationY = 0;
        engine.mouse.x = 0;
        engine.mouse.y = 0;
        engine.mouse.targetX = 0;
        engine.mouse.targetY = 0;
    }

    // Setup Mouse & Drag Interactions
    function setupInteractions() {
        window.addEventListener('mousemove', function (e) {
            var normX = (e.clientX / window.innerWidth) * 2 - 1;
            var normY = -(e.clientY / window.innerHeight) * 2 + 1;
            engine.mouse.targetX = normX * 0.65;
            engine.mouse.targetY = normY * 0.45;
        });

        if (engine.canvas) {
            engine.canvas.addEventListener('mousedown', function (e) {
                engine.isDragging = true;
                engine.prevMouseX = e.clientX;
                engine.prevMouseY = e.clientY;
            });

            window.addEventListener('mouseup', function () {
                engine.isDragging = false;
            });

            window.addEventListener('mousemove', function (e) {
                if (engine.isDragging) {
                    var deltaX = e.clientX - engine.prevMouseX;
                    var deltaY = e.clientY - engine.prevMouseY;
                    engine.manualRotationY += deltaX * 0.01;
                    engine.manualRotationX += deltaY * 0.01;
                    engine.prevMouseX = e.clientX;
                    engine.prevMouseY = e.clientY;
                }
            });

            engine.canvas.addEventListener('dblclick', resetCamera);
        }

        window.addEventListener('resize', onWindowResize);
    }

    function onWindowResize() {
        if (!engine.container || !engine.renderer || !engine.camera) return;
        var width = engine.container.clientWidth || 300;
        var height = engine.container.clientHeight || 260;
        engine.camera.aspect = width / height;
        engine.camera.updateProjectionMatrix();
        engine.renderer.setSize(width, height);
    }

    // Main 3D Animation & Render Loop
    function renderLoop() {
        requestAnimationFrame(renderLoop);
        engine.clock += 0.016;

        // Smooth Mouse Interpolation (Lerp)
        engine.mouse.x += (engine.mouse.targetX - engine.mouse.x) * 0.08;
        engine.mouse.y += (engine.mouse.targetY - engine.mouse.y) * 0.08;

        // Animate 3D Robot
        if (engine.robotGroup && engine.robotGroup.visible) {
            var hoverY = Math.sin(engine.clock * 2.0) * 0.04;
            engine.robotGroup.position.y = -0.75 + hoverY;

            // Robot head tracks mouse
            if (engine.headMesh) {
                engine.headMesh.rotation.y = engine.mouse.x * 0.8 + engine.manualRotationY;
                engine.headMesh.rotation.x = -engine.mouse.y * 0.5 + engine.manualRotationX;
            }

            // Body subtle sway
            engine.robotGroup.rotation.y = engine.manualRotationY + Math.sin(engine.clock * 0.8) * 0.05;

            // Pulse Reactor Core
            if (engine.reactorMesh) {
                var pulse = 1.0 + Math.sin(engine.clock * 4.0) * 0.12 + (engine.isSpeaking ? 0.25 : 0);
                engine.reactorMesh.scale.set(pulse, pulse, pulse);
            }
        }

        // Animate 3D Neural Cortex
        if (engine.brainGroup && engine.brainGroup.visible) {
            engine.brainGroup.rotation.y = engine.clock * 0.35 + engine.manualRotationY;
            engine.brainGroup.rotation.x = engine.mouse.y * 0.25 + engine.manualRotationX;
        }

        // Animate 3D Quantum Core
        if (engine.coreGroup && engine.coreGroup.visible) {
            if (engine.torusRings[0]) {
                engine.torusRings[0].rotation.x += 0.015;
                engine.torusRings[0].rotation.y += 0.025;
            }
            if (engine.torusRings[1]) {
                engine.torusRings[1].rotation.y -= 0.02;
                engine.torusRings[1].rotation.z += 0.018;
            }
            if (engine.torusRings[2]) {
                engine.torusRings[2].rotation.z += 0.022;
                engine.torusRings[2].rotation.x -= 0.012;
            }
            engine.coreGroup.rotation.y = engine.manualRotationY;
        }

        // Animate Particle Aura
        if (engine.particlesGroup) {
            engine.particlesGroup.rotation.y = engine.clock * 0.1;
        }

        if (engine.renderer && engine.scene && engine.camera) {
            engine.renderer.render(engine.scene, engine.camera);
        }
    }

    // 2D Canvas Procedural Fallback (Zero dependency fallback)
    function init2DFallback() {
        var canvas = document.getElementById('bot3dCanvas');
        if (!canvas) return;
        var ctx = canvas.getContext('2d');
        var t = 0;

        function draw2D() {
            t += 0.03;
            var w = canvas.width = canvas.clientWidth || 300;
            var h = canvas.height = canvas.clientHeight || 260;
            ctx.clearRect(0, 0, w, h);

            var cx = w / 2;
            var cy = h / 2 + Math.sin(t * 2) * 6;

            // Halo Rings
            ctx.strokeStyle = '#22c55e';
            ctx.lineWidth = 2;
            ctx.shadowBlur = 16;
            ctx.shadowColor = '#22c55e';
            ctx.beginPath();
            ctx.arc(cx, cy, 65 + Math.sin(t * 3) * 4, 0, Math.PI * 2);
            ctx.stroke();

            // Head
            ctx.fillStyle = '#0d1a12';
            ctx.fillRect(cx - 35, cy - 40, 70, 50);
            ctx.strokeStyle = '#22c55e';
            ctx.strokeRect(cx - 35, cy - 40, 70, 50);

            // Eyes
            ctx.fillStyle = '#38bdf8';
            ctx.shadowColor = '#38bdf8';
            ctx.beginPath();
            ctx.arc(cx - 15, cy - 15, 6, 0, Math.PI * 2);
            ctx.arc(cx + 15, cy - 15, 6, 0, Math.PI * 2);
            ctx.fill();

            requestAnimationFrame(draw2D);
        }
        draw2D();
    }

    // Audio Visualizer Spectrum Engine
    function initAudioVisualizer() {
        var vCanvas = document.getElementById('audioWaveCanvas');
        if (!vCanvas) return;
        var ctx = vCanvas.getContext('2d');
        var t = 0;

        function drawWave() {
            t += 0.05;
            var w = vCanvas.width = vCanvas.clientWidth || 500;
            var h = vCanvas.height = vCanvas.clientHeight || 28;
            ctx.clearRect(0, 0, w, h);

            var bars = 36;
            var barWidth = (w / bars) - 3;
            var active = window.atomIsTyping || engine.isSpeaking;

            for (var i = 0; i < bars; i++) {
                var freq = Math.sin(t * 2.5 + i * 0.3) * Math.cos(t * 1.5 - i * 0.2);
                var amp = active ? (Math.abs(freq) * 0.85 + 0.15) : (Math.abs(freq) * 0.25 + 0.05);
                var barHeight = Math.max(3, amp * h);

                var grad = ctx.createLinearGradient(0, h, 0, 0);
                grad.addColorStop(0, '#22c55e');
                grad.addColorStop(1, '#38bdf8');

                ctx.fillStyle = grad;
                ctx.shadowColor = '#22c55e';
                ctx.shadowBlur = active ? 8 : 2;
                ctx.fillRect(i * (barWidth + 3), h - barHeight, barWidth, barHeight);
            }
            requestAnimationFrame(drawWave);
        }
        drawWave();
    }

    // Background Interactive Matrix / Particle FX
    function initBackgroundParticles() {
        var bgCanvas = document.getElementById('bgParticleCanvas');
        if (!bgCanvas) return;
        var ctx = bgCanvas.getContext('2d');
        var particles = [];
        var num = 45;

        function resize() {
            bgCanvas.width = window.innerWidth;
            bgCanvas.height = window.innerHeight;
        }
        resize();
        window.addEventListener('resize', resize);

        for (var i = 0; i < num; i++) {
            particles.push({
                x: Math.random() * bgCanvas.width,
                y: Math.random() * bgCanvas.height,
                vx: (Math.random() - 0.5) * 0.4,
                vy: (Math.random() - 0.5) * 0.4,
                size: Math.random() * 1.8 + 0.6
            });
        }

        function drawBg() {
            ctx.clearRect(0, 0, bgCanvas.width, bgCanvas.height);
            ctx.fillStyle = 'rgba(34, 197, 94, 0.35)';
            ctx.strokeStyle = 'rgba(34, 197, 94, 0.08)';

            for (var i = 0; i < num; i++) {
                var p = particles[i];
                p.x += p.vx;
                p.y += p.vy;
                if (p.x < 0 || p.x > bgCanvas.width) p.vx *= -1;
                if (p.y < 0 || p.y > bgCanvas.height) p.vy *= -1;

                ctx.beginPath();
                ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
                ctx.fill();

                for (var j = i + 1; j < num; j++) {
                    var p2 = particles[j];
                    var dx = p.x - p2.x;
                    var dy = p.y - p2.y;
                    var dist = Math.sqrt(dx * dx + dy * dy);
                    if (dist < 100) {
                        ctx.lineWidth = 1 - (dist / 100);
                        ctx.beginPath();
                        ctx.moveTo(p.x, p.y);
                        ctx.lineTo(p2.x, p2.y);
                        ctx.stroke();
                    }
                }
            }
            requestAnimationFrame(drawBg);
        }
        drawBg();
    }

    // Expose Global 3D Controls
    window.atom3D = {
        setMode: setMode,
        toggleWireframe: toggleWireframe,
        resetCamera: resetCamera,
        setSpeaking: function (val) { engine.isSpeaking = !!val; }
    };

    // Auto-initialize on load
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        init3D();
        initAudioVisualizer();
        initBackgroundParticles();
    } else {
        window.addEventListener('DOMContentLoaded', function () {
            init3D();
            initAudioVisualizer();
            initBackgroundParticles();
        });
    }
})();
