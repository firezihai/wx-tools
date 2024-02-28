from pilk import decode
import subprocess
import os.path
import sys

arg1 = sys.argv[1]
arg2 = sys.argv[2]

def decodeSilk(silk_path,pcm_path):
    try:
        decode(silk_path, pcm_path, 44100)
    except Exception as e:
        print(f"Error: {e}")
    finally:
        print(pcm_path)

decodeSilk(arg1,arg2)