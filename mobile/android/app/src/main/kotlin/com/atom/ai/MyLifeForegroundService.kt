package com.atom.ai

import android.app.Notification
import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.PendingIntent
import android.app.Service
import android.content.Context
import android.content.Intent
import android.hardware.Sensor
import android.hardware.SensorEvent
import android.hardware.SensorEventListener
import android.hardware.SensorManager
import android.os.Build
import android.os.IBinder
import android.os.PowerManager
import androidx.core.app.NotificationCompat

/**
 * MyLifeForegroundService — Keeps the MyLife OS local REST API server alive
 * and executes low-power background step counting on the Samsung Galaxy M31.
 */
class MyLifeForegroundService : Service(), SensorEventListener {

    companion object {
        const val CHANNEL_ID = "mylife_server_channel"
        const val NOTIFICATION_ID = 8765
        const val ACTION_START = "com.atom.ai.ACTION_START_SERVER"
        const val ACTION_STOP = "com.atom.ai.ACTION_STOP_SERVER"
        const val ACTION_PAUSE = "com.atom.ai.ACTION_PAUSE_TRACKING"
        const val ACTION_RESUME = "com.atom.ai.ACTION_RESUME_TRACKING"

        var isServerRunning: Boolean = false
            private set
        var isTrackingPaused: Boolean = false
            private set
        var liveStepCount: Int = 0
            private set
    }

    private var wakeLock: PowerManager.WakeLock? = null
    private var sensorManager: SensorManager? = null
    private var stepSensor: Sensor? = null
    private var initialStepOffset: Int = -1

    override fun onCreate() {
        super.onCreate()
        createNotificationChannel()

        // Initialize low-power hardware step counter
        sensorManager = getSystemService(Context.SENSOR_SERVICE) as? SensorManager
        stepSensor = sensorManager?.getDefaultSensor(Sensor.TYPE_STEP_COUNTER)
        stepSensor?.let {
            sensorManager?.registerListener(this, it, SensorManager.SENSOR_DELAY_NORMAL)
        }
    }

    override fun onStartCommand(intent: Intent?, flags: Int, startId: Int): Int {
        when (intent?.action) {
            ACTION_STOP -> {
                stopSelf()
                return START_NOT_STICKY
            }
            ACTION_PAUSE -> {
                isTrackingPaused = true
                updateNotification("Tracking Paused • Server Active")
            }
            ACTION_RESUME -> {
                isTrackingPaused = false
                updateNotification("Active • Tracking Enabled")
            }
            else -> {
                isServerRunning = true
                isTrackingPaused = false
                acquireWakeLock()
                startForeground(NOTIFICATION_ID, buildNotification("Active • Port 8765"))
            }
        }
        return START_STICKY
    }

    private fun acquireWakeLock() {
        if (wakeLock == null) {
            val powerManager = getSystemService(Context.POWER_SERVICE) as PowerManager
            wakeLock = powerManager.newWakeLock(
                PowerManager.PARTIAL_WAKE_LOCK,
                "MyLifeOS::ServerWakeLock"
            ).apply {
                setReferenceCounted(false)
                acquire(12 * 60 * 60 * 1000L) // 12-hour safe auto-release
            }
        }
    }

    private fun createNotificationChannel() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            val channel = NotificationChannel(
                CHANNEL_ID,
                "MyLife OS Local Server",
                NotificationManager.IMPORTANCE_LOW
            ).apply {
                description = "Keeps the local personal API server active on Samsung Galaxy M31"
                setShowBadge(false)
            }
            val manager = getSystemService(NotificationManager::class.java)
            manager?.createNotificationChannel(channel)
        }
    }

    private fun buildNotification(statusText: String): Notification {
        val launchIntent = packageManager.getLaunchIntentForPackage(packageName)
        val pendingIntent = PendingIntent.getActivity(
            this, 0, launchIntent,
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE
        )

        val pauseIntent = Intent(this, MyLifeActionReceiver::class.java).apply {
            action = if (isTrackingPaused) ACTION_RESUME else ACTION_PAUSE
        }
        val pausePendingIntent = PendingIntent.getBroadcast(
            this, 1, pauseIntent,
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE
        )

        val stopIntent = Intent(this, MyLifeActionReceiver::class.java).apply {
            action = ACTION_STOP
        }
        val stopPendingIntent = PendingIntent.getBroadcast(
            this, 2, stopIntent,
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE
        )

        val toggleActionTitle = if (isTrackingPaused) "Resume Tracking" else "Pause Tracking"

        return NotificationCompat.Builder(this, CHANNEL_ID)
            .setContentTitle("MyLife OS Server (Port 8765)")
            .setContentText(statusText)
            .setSmallIcon(android.R.drawable.ic_menu_compass)
            .setContentIntent(pendingIntent)
            .setOngoing(true)
            .setPriority(NotificationCompat.PRIORITY_LOW)
            .addAction(android.R.drawable.ic_media_pause, toggleActionTitle, pausePendingIntent)
            .addAction(android.R.drawable.ic_menu_close_clear_cancel, "Stop", stopPendingIntent)
            .build()
    }

    private fun updateNotification(statusText: String) {
        val manager = getSystemService(Context.NOTIFICATION_SERVICE) as NotificationManager
        manager.notify(NOTIFICATION_ID, buildNotification(statusText))
    }

    override fun onSensorChanged(event: SensorEvent?) {
        if (isTrackingPaused || event == null) return

        if (event.sensor.type == Sensor.TYPE_STEP_COUNTER) {
            val totalSteps = event.values[0].toInt()
            if (initialStepOffset < 0) {
                initialStepOffset = totalSteps
            }
            liveStepCount = (totalSteps - initialStepOffset).coerceAtLeast(0)
        }
    }

    override fun onAccuracyChanged(sensor: Sensor?, accuracy: Int) {}

    override fun onDestroy() {
        super.onDestroy()
        isServerRunning = false
        sensorManager?.unregisterListener(this)
        wakeLock?.let {
            if (it.isHeld) it.release()
        }
        wakeLock = null
    }

    override fun onBind(intent: Intent?): IBinder? = null
}
