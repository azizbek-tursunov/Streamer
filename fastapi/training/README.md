# Classroom YOLO Training

Use this structure for the classroom occupancy model:

```text
fastapi/
  datasets/
    classroom_people/
      images/
        train/
        val/
        test/
      labels/
        train/
        val/
        test/
```

## Labeling rules

- Use a single class: `person`
- Label every visible student or instructor that should count toward occupancy
- Keep boxes tight around visible people
- If desks block lower bodies, annotate the visible upper body or head region consistently
- Do not label posters, screen faces, reflections, or people outside the classroom area
- Exclude people visible through windows or hallways unless they should count for that camera

## Split target

- `train`: 70%
- `val`: 20%
- `test`: 10%

Each split should include:

- front-facing classrooms
- rear camera angles
- side angles
- sparse rooms
- full rooms
- occluded desks and crowded rows
- different lighting conditions

## Recommended dataset strategy

1. Start from `yolo26n.pt` for fast CPU inference.
2. Fine-tune with your classroom images using the template YAML in this folder.
3. Validate per camera angle, not only overall mAP.
4. Promote the custom weights only after spot-checking count accuracy on real snapshots.

## Training example

Run inside the `yolo` container or another Python environment with the same dependencies:

```bash
yolo detect train \
  model=yolo26n.pt \
  data=/app/training/classroom_people.yaml \
  epochs=80 \
  imgsz=1280 \
  batch=8 \
  device=cpu
```

## Deployment target

After training, set `YOLO_MODEL` to the exported best weights, for example:

```env
YOLO_MODEL=runs/detect/train/weights/best.pt
```
