{{-- resources/views/custom/notification-sounds.blade.php --}}

<script>
document.addEventListener('DOMContentLoaded', function() {
    let audioContext;
    let currentVolume = 0.6; // Default volume

    // Initialize audio context
    function initAudioContext() {
        if (!audioContext) {
            audioContext = new (window.AudioContext || window.webkitAudioContext)();
        }
        return audioContext;
    }

    // Play notification sound based on type
    function playNotificationSound(type) {
        try {
            const ctx = initAudioContext();
            
            // Resume audio context if it's suspended (browser autoplay policy)
            if (ctx.state === 'suspended') {
                ctx.resume();
            }
            
            // Different sound patterns for different notification types
            const soundPatterns = {
                success: {
                    frequencies: [523.25, 659.25, 783.99], // C5, E5, G5 - pleasant ascending chord
                    type: 'sine',
                    duration: 0.3,
                    delay: 100
                },
                error: {
                    frequencies: [220, 185, 165], // A3, F#3, E3 - descending warning tones
                    type: 'sawtooth',
                    duration: 0.4,
                    delay: 120
                },
                warning: {
                    frequencies: [440, 493.88, 440], // A4, B4, A4 - warning pattern
                    type: 'square',
                    duration: 0.25,
                    delay: 150
                },
                info: {
                    frequencies: [261.63, 329.63], // C4, E4 - simple info chime
                    type: 'sine',
                    duration: 0.3,
                    delay: 200
                }
            };

            const pattern = soundPatterns[type] || soundPatterns.info;
            
            // Play each frequency in sequence
            pattern.frequencies.forEach((frequency, index) => {
                setTimeout(() => {
                    const oscillator = ctx.createOscillator();
                    const gainNode = ctx.createGain();
                    
                    oscillator.connect(gainNode);
                    gainNode.connect(ctx.destination);
                    
                    oscillator.frequency.setValueAtTime(frequency, ctx.currentTime);
                    oscillator.type = pattern.type;
                    
                    // Envelope for smooth sound
                    gainNode.gain.setValueAtTime(0, ctx.currentTime);
                    gainNode.gain.linearRampToValueAtTime(currentVolume * 0.3, ctx.currentTime + 0.05);
                    gainNode.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + pattern.duration);
                    
                    oscillator.start(ctx.currentTime);
                    oscillator.stop(ctx.currentTime + pattern.duration);
                }, index * pattern.delay);
            });
            
        } catch (error) {
            console.warn('Could not play notification sound:', error);
        }
    }

    // Listen for Livewire events
    window.addEventListener('play-notification-sound', function(event) {
        const soundType = event.detail?.type || 'info';
        playNotificationSound(soundType);
    });

    // Also listen for Filament notifications being displayed
    // This will catch any notification that appears on the page
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) { // Element node
                        // Check if it's a Filament notification
                        const notification = node.querySelector?.('[role="alert"]') || 
                                           (node.getAttribute?.('role') === 'alert' ? node : null);
                        
                        if (notification) {
                            // Determine notification type based on classes or content
                            let soundType = 'info';
                            
                            if (notification.classList.contains('fi-no-danger') || 
                                notification.innerHTML.includes('danger') ||
                                notification.innerHTML.includes('error') ||
                                notification.innerHTML.includes('Invalid') ||
                                notification.innerHTML.includes('Not Found')) {
                                soundType = 'error';
                            } else if (notification.classList.contains('fi-no-success') || 
                                      notification.innerHTML.includes('success') ||
                                      notification.innerHTML.includes('Selected')) {
                                soundType = 'success';
                            } else if (notification.classList.contains('fi-no-warning') || 
                                      notification.innerHTML.includes('warning')) {
                                soundType = 'warning';
                            }
                            
                            // Small delay to ensure notification is visible
                            setTimeout(() => {
                                playNotificationSound(soundType);
                            }, 100);
                        }
                    }
                });
            }
        });
    });

    // Start observing
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });

    // Enable audio context on first user interaction (required by browsers)
    document.addEventListener('click', function enableAudio() {
        initAudioContext();
        document.removeEventListener('click', enableAudio);
    }, { once: true });

    // Also enable on keypress (for barcode scanners)
    document.addEventListener('keydown', function enableAudioOnKey() {
        initAudioContext();
        document.removeEventListener('keydown', enableAudioOnKey);
    }, { once: true });
});
</script>

<style>
/* Optional: Add some visual feedback for audio status */
.notification-sound-indicator {
    position: fixed;
    top: 10px;
    right: 10px;
    background: rgba(0,0,0,0.7);
    color: white;
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 12px;
    z-index: 9999;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.notification-sound-indicator.active {
    opacity: 1;
}
</style>