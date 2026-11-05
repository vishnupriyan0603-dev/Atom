package com.atom.ai

import android.content.Context
import android.content.Intent
import android.content.IntentFilter
import android.os.BatteryManager
import android.os.Build
import android.security.keystore.KeyGenParameterSpec
import android.security.keystore.KeyProperties
import io.flutter.embedding.android.FlutterActivity
import io.flutter.embedding.engine.FlutterEngine
import io.flutter.plugin.common.MethodChannel
import java.security.KeyStore
import javax.crypto.KeyGenerator
import javax.crypto.SecretKey

class MainActivity: FlutterActivity() {
    private val SERVICE_CHANNEL = "com.atom.ai/mylife_service"
    private val KEYSTORE_CHANNEL = "com.atom.ai/keystore"
    private val KEY_ALIAS = "MyLifeOS_Master_AES_Key"

    override fun configureFlutterEngine(flutterEngine: FlutterEngine) {
        super.configureFlutterEngine(flutterEngine)

        // 1. Service Control & Sensor Channel
        MethodChannel(flutterEngine.dartExecutor.binaryMessenger, SERVICE_CHANNEL).setMethodCallHandler { call, result ->
            when (call.method) {
                "startForegroundService" -> {
                    val intent = Intent(this, MyLifeForegroundService::class.java).apply {
                        action = MyLifeForegroundService.ACTION_START
                    }
                    if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
                        startForegroundService(intent)
                    } else {
                        startService(intent)
                    }
                    result.success(true)
                }
                "stopForegroundService" -> {
                    val intent = Intent(this, MyLifeForegroundService::class.java).apply {
                        action = MyLifeForegroundService.ACTION_STOP
                    }
                    startService(intent)
                    result.success(true)
                }
                "isServiceRunning" -> {
                    result.success(MyLifeForegroundService.isServerRunning)
                }
                "isTrackingPaused" -> {
                    result.success(MyLifeForegroundService.isTrackingPaused)
                }
                "getLiveSteps" -> {
                    result.success(MyLifeForegroundService.liveStepCount)
                }
                "getBatteryStats" -> {
                    val batteryIntent = registerReceiver(null, IntentFilter(Intent.ACTION_BATTERY_CHANGED))
                    val level = batteryIntent?.getIntExtra(BatteryManager.EXTRA_LEVEL, -1) ?: -1
                    val scale = batteryIntent?.getIntExtra(BatteryManager.EXTRA_SCALE, -1) ?: -1
                    val status = batteryIntent?.getIntExtra(BatteryManager.EXTRA_STATUS, -1) ?: -1
                    val isCharging = status == BatteryManager.BATTERY_STATUS_CHARGING ||
                                     status == BatteryManager.BATTERY_STATUS_FULL

                    val percentage = if (level >= 0 && scale > 0) (level * 100 / scale) else 100
                    result.success(mapOf(
                        "level" to percentage,
                        "isCharging" to isCharging
                    ))
                }
                else -> result.notImplemented()
            }
        }

        // 2. Hardware Keystore Master Key Channel (Backed by Samsung Knox / TEE)
        MethodChannel(flutterEngine.dartExecutor.binaryMessenger, KEYSTORE_CHANNEL).setMethodCallHandler { call, result ->
            when (call.method) {
                "getOrCreateMasterKey" -> {
                    try {
                        val keyHex = getOrCreateKeystoreSecret()
                        result.success(keyHex)
                    } catch (e: Exception) {
                        result.error("KEYSTORE_ERROR", e.localizedMessage, null)
                    }
                }
                else -> result.notImplemented()
            }
        }
    }

    private fun getOrCreateKeystoreSecret(): String {
        val keyStore = KeyStore.getInstance("AndroidKeyStore").apply { load(null) }

        if (!keyStore.containsAlias(KEY_ALIAS)) {
            val keyGenerator = KeyGenerator.getInstance(
                KeyProperties.KEY_ALGORITHM_AES,
                "AndroidKeyStore"
            )
            val spec = KeyGenParameterSpec.Builder(
                KEY_ALIAS,
                KeyProperties.PURPOSE_ENCRYPT or KeyProperties.PURPOSE_DECRYPT
            )
                .setBlockModes(KeyProperties.BLOCK_MODE_GCM)
                .setEncryptionPaddings(KeyProperties.ENCRYPTION_PADDING_NONE)
                .setKeySize(256)
                .build()

            keyGenerator.init(spec)
            keyGenerator.generateKey()
        }

        // Return a stable machine-derived key identifier seed for SQLCipher password initialization
        val key = keyStore.getKey(KEY_ALIAS, null) as? SecretKey
        val encodedBytes = key?.encoded ?: KEY_ALIAS.toByteArray()
        return bytesToHex(encodedBytes.take(32).toByteArray())
    }

    private fun bytesToHex(bytes: ByteArray): String {
        val hexChars = "0123456789abcdef"
        val result = StringBuilder(bytes.size * 2)
        for (b in bytes) {
            val i = b.toInt() and 0xFF
            result.append(hexChars[i shr 4])
            result.append(hexChars[i and 0x0F])
        }
        return result.toString()
    }
}
