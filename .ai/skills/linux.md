# Linux General Reference

## File Operations
```bash
ls -lah                    # list with permissions, size
cp -r source destination   # recursive copy
mv source destination      # move/rename
rm -rf directory           # remove recursively (dangerous)
find . -name "*.php"       # find files
grep -r "pattern" .        # search content
```

## Permissions
- `r=4`, `w=2`, `x=1`
- `chmod 755 file` - rwxr-xr-x (owner all, group/other read+exec).
- `chmod 644 file` - rw-r--r-- (owner rw, group/other read).
- `chown user:group file` - change owner/group.

## Process Management
```bash
ps aux              # all processes
top / htop         # live process view
kill -9 PID        # force kill
nohup command &    # run in background
screen / tmux      # persistent sessions
```
