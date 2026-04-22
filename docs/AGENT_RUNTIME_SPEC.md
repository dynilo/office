# AGENT_RUNTIME_SPEC.md

## Runtime entities
The system will eventually include these core concepts:
- Agent
- AgentProfile
- Task
- TaskDependency
- Execution
- ExecutionLog
- Document
- KnowledgeItem

## Runtime principles
- Agents are explicitly defined and versionable
- Tasks are structured and auditable
- Executions have clear lifecycle states
- Outputs should be persistable and reviewable
- Documents and knowledge should support future retrieval

## Important note
This file describes the intended runtime direction.
It does not mean every concept should be implemented immediately.
Only implement what the current slice explicitly requires.

## Early slice guidance
For Slice 01:
- do not implement runtime logic yet

For Slice 02:
- create only the foundational structure

For Slice 03:
- implement only the first schema version defined by that slice
