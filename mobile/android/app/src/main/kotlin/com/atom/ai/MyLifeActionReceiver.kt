package com.atom.ai

import android.content.BroadcastReceiver
import android.content.Context
import android.content.Intent

/**
 * MyLifeActionReceiver — Handles notification action clicks (Pause, Resume, Stop)
 * for MyLifeForegroundService.
 */
class MyLifeActionReceiver : BroadcastReceiver() {
    override fun onReceive(context: Context?, intent: Intent?) {
        if (context == null || intent == null) return

        val serviceIntent = Intent(context, MyLifeForegroundService::class.java).apply {
            action = intent.action
        }

        context.startService(serviceIntent)
    }
}

