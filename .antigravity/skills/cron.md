# Cron Reference

## Syntax
```
* * * * * command
│ │ │ │ │
│ │ │ │ └── Day of week (0-7, 0/7=Sun)
│ │ │ └──── Month (1-12)
│ │ └────── Day of month (1-31)
│ └──────── Hour (0-23)
└────────── Minute (0-59)
```

## Special Strings
- `@reboot` - on boot
- `@daily` - once per day (0 0 * * *)
- `@hourly` - once per hour (0 * * * *)
- `@weekly` - once per week (0 0 * * 0)
- `@monthly` - once per month (0 0 1 * *)

## Managing Crontab
```bash
crontab -e              # edit user crontab
crontab -l              # list crontab
crontab -r              # remove crontab
```

## Best Practices
- Use absolute paths for commands and files.
- Redirect output: `>> /var/log/cron.log 2>&1`
- Log errors separately.
- Use `flock` to prevent overlapping runs: `flock -n /tmp/lockfile.lock command`
- Test commands manually before adding to cron.
