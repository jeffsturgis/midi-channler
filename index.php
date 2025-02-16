<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>MIDI Keyboard Channel 2 and 3</title>
  </head>
  <body>
    <script>
      let midiAccess;
      // Map to keep track of the last time a note was pressed or released
      let lastNoteActionTime = new Map();
      // Debounce time in milliseconds (adjust as needed)
      const DEBOUNCE_TIME = 50;

      function onMIDIInit(midi) {
        midiAccess = midi;
        console.log("MIDI ready!");
        const inputs = midiAccess.inputs.values();
        for (let input of inputs) {
          input.onmidimessage = handleMIDIMessage;
        }
      }

      function onMIDIReject(err) {
        console.log("MIDI access failed", err);
      }

      function handleMIDIMessage(event) {
        let [status, note, velocity] = event.data;

        if (
          (status >= 0x90 && status <= 0x9f) ||
          (status >= 0x80 && status <= 0x8f)
        ) {
          let channel = status & 0x0f;
          let command = status & 0xf0;

          if (command === 0x90 && velocity === 0) {
            command = 0x80; // Treat velocity 0 as note off
          }

          // Current timestamp
          const now = Date.now();

          // Check if this is a note on or off event
          if (command === 0x90 || command === 0x80) {
            // If this note was recently acted upon, ignore this event
            if (
              lastNoteActionTime.has(note) &&
              now - lastNoteActionTime.get(note) < DEBOUNCE_TIME
            ) {
              console.log(`Note ${note} debounced`);
              return;
            }

            // Update the last action time for this note
            lastNoteActionTime.set(note, now);

            // Process the note event
            if (command === 0x90) {
              // Note on
              if (note < 60) {
                console.log(`Note ${note} ON routed to Channel 2`);
                sendMIDIMessage(0x90, note, velocity, 2);
              } else {
                console.log(`Note ${note} ON routed to Channel 3`);
                sendMIDIMessage(0x90, note, velocity, 3);
              }
            } else {
              // Note off
              if (note < 60) {
                console.log(`Note ${note} OFF routed to Channel 2`);
                sendMIDIMessage(0x80, note, velocity, 2);
              } else {
                console.log(`Note ${note} OFF routed to Channel 3`);
                sendMIDIMessage(0x80, note, velocity, 3);
              }
            }
          }
        }
      }

      function sendMIDIMessage(command, note, velocity, channel) {
        if (midiAccess && midiAccess.outputs) {
          const outputs = midiAccess.outputs.values();
          for (let output of outputs) {
            output.send([command | channel, note, velocity]);
          }
        } else {
          console.error(
            "No MIDI outputs available or MIDI access not granted."
          );
        }
      }

      // Request MIDI access
      if (navigator.requestMIDIAccess) {
        navigator.requestMIDIAccess().then(onMIDIInit, onMIDIReject);
      } else {
        console.warn("Web MIDI API not supported in this browser.");
      }
    </script>
    <img src="channel-separator.png" />
  </body>
</html>
