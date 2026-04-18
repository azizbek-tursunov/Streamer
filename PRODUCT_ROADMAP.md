# UniVision Product Roadmap

## Goal

Turn UniVision from a camera monitoring system into an academic operations product for universities.

The product should help universities:

- verify whether lessons are actually happening
- detect classroom anomalies early
- improve auditorium utilization
- reduce manual supervision
- give deans and administrators clear operational visibility

## Product Positioning

UniVision should be sold as:

> An academic operations and classroom compliance platform that helps universities monitor lesson activity, detect anomalies, improve room utilization, and reduce manual supervision.

## Product Principles

- Solve administrative pain, not only technical problems
- Show actionable information, not just video
- Build role-specific value for deans, admins, and IT staff
- Treat AI as assistive and advisory, not absolute truth
- Prefer operational workflows over passive dashboards

## Phase 1: High-Value Operational Features

This phase creates the strongest business value and should be built first.

### 1. Anomaly Detection Rules

Detect and surface cases such as:

- lesson scheduled but room appears empty
- people detected but no lesson is scheduled
- camera offline during a scheduled lesson
- stale snapshot or stale AI people count
- HEMIS lesson data missing for expected rooms

### 2. Alerts and Notifications

Create actionable alerts for deans and administrators:

- unread anomaly alerts
- faculty-level anomaly summaries
- room-specific incident details
- acknowledgment and resolution status

### 3. Dean and Faculty Dashboard

Provide a faculty-focused operational dashboard:

- today’s active lessons
- rooms with anomalies
- no-show lessons
- rooms used without planned lessons
- faculty-level occupancy summary

### 4. Weekly and Monthly Reporting

Export reports that management can actually use:

- lesson compliance summary
- auditorium utilization by building/faculty
- anomaly counts by category
- no-show and mismatch trends

## Phase 2: Accountability and Resource Optimization

### 5. Incident Workflow

Each anomaly should be manageable as an operational case:

- open
- acknowledged
- resolved
- dismissed

Each case should support:

- assignee
- note/history log
- timestamps
- related auditorium/camera

### 6. Historical Analytics

Add decision-support analytics:

- occupancy trend by room
- occupancy trend by building
- busiest hours
- most underused rooms
- actual occupancy versus room capacity

### 7. Room Utilization Planning

Help universities optimize resources:

- identify overbooked rooms
- identify low-efficiency rooms
- compare room capacity to actual attendance
- suggest better room assignment patterns

### 8. Audit Log

Track administrative actions:

- faculty assignments
- camera assignments
- sync actions
- reordered rooms/buildings
- feedback changes
- role and permission changes

## Phase 3: Product Maturity and Differentiation

### 9. AI Trust Layer

Strengthen confidence in AI-based counting:

- show count freshness
- show AI confidence or quality hints
- allow manual correction
- record correction history
- create a review loop for bad detections

### 10. Operations Health Dashboard

Support internal system maintenance:

- offline cameras
- failed snapshot generation
- stale streams
- queue lag
- YOLO processing failures
- HEMIS sync status and failures

### 11. Room History Page

For each auditorium, show:

- lesson timeline
- recent people-count history
- recent snapshots
- related alerts
- feedback history

### 12. Mobile-Friendly Dean Experience

Build a fast lightweight mobile view for:

- active anomalies
- current lessons
- key room status
- alert acknowledgment

## Priority by Business Value

Build in this order:

1. anomaly detection rules
2. alerts and notifications
3. dean/faculty dashboard
4. weekly and monthly reporting
5. incident workflow
6. historical analytics
7. room utilization planning
8. operations health dashboard
9. AI trust layer
10. mobile dean experience

## Recommended Next Build

The next implementation target should be:

### Operational Anomaly Alerts

This is the fastest path to a more valuable product because it converts existing data into decisions.

#### Why this first

- the project already has lessons, auditoriums, camera snapshots, and people counts
- it creates immediate value for deans and admins
- it makes the platform feel operational instead of observational
- it unlocks dashboards, reports, and workflows later

#### Initial anomaly types

- `lesson_no_people`
- `people_no_lesson`
- `camera_offline_during_lesson`
- `stale_people_count`
- `stale_snapshot`

#### Minimum implementation

- anomaly table in database
- scheduled job to generate anomalies
- basic status: `open`, `resolved`, `dismissed`
- anomaly list page
- dean/admin notifications
- auditorium badge or warning indicator

## Suggested Phase 1 Delivery Plan

### Milestone 1

- define anomaly rules
- create anomaly schema
- create scheduled anomaly detection job
- show anomaly badges on auditorium cards and room detail pages

### Milestone 2

- add anomaly list page
- add filtering by status, building, faculty, type
- add dean notification count
- add anomaly detail modal/page

### Milestone 3

- add acknowledge/resolve/dismiss actions
- add notes/history
- add weekly summary export

## Success Metrics

Track whether the product is actually becoming more valuable:

- number of anomaly cases detected per day
- anomaly resolution time
- no-show lesson rate
- rooms with repeated mismatches
- faculty-level room utilization rate
- number of resolved incidents by deans/admins

## Current Product Gaps To Address Along The Way

- enforce faculty-scoped access consistently across auditorium pages and APIs
- refresh project documentation to match the real platform
- expose system-health information to admins
- make AI outputs clearly advisory, not absolute

## Working Rule

When choosing new features, prefer:

- actions over passive data
- role-specific value over generic UI additions
- features that improve accountability, reporting, and operational decisions

Avoid spending time on lower-value additions before the anomaly and reporting layer is solid.
