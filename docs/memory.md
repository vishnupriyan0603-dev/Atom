# Atom AI - Memory System

This document outlines the multi-layered memory architecture implemented in Atom AI.

## Memory Layers

| Layer | Type | Scope / Purpose | Storage Format |
|-------|------|-----------------|----------------|
| **Layer 1** | Working Memory | Dynamic RAM context: current conversation, active request, local variables. | In-Memory (PHP Array) |
| **Layer 2** | Session Memory | Active session state: current project, opened files, recent commands, active task. | In-Memory (Session Array) |
| **Layer 3** | Long-Term Memory | Persistent user decisions, preferences, and technical resolutions. | MySQL Database (`atom_memories`) |
| **Personal** | Personal Profile | Vichu's personal identity, learning goals, and career objectives. | JSON File (`storage/profile/personal.json`) |
| **Project** | Project Memory | Project-specific configuration, framework, environment details. | MySQL (`atom_memories` keyed to `project_id`) |
