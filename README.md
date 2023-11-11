# beesmartvideos
An internal tool I used to create Math videos for Thai high school students, after realizing the available tools were too hard to learn

It's still in early infancy and still only for personal use, but it's usable, it works, and if someone needs a simple Math graph video creator, give it a try.
As usual, docs are the last things written, but you can start from the examples.

This is how it works (at least, in the initial release):

- An animation is made up of scenes
- You can add several type of objects to an animation
- You tell the class to create the video
- What it actually does, according to the setup, is to create a sequence of PNG
- These will be transformed into a MP4 by ffmpeg, a lovely single EXE for Windows that I will check if I can include in this repo (it's like 200MB!), otherwise you can download it yourself
