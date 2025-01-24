interface Details{
    author:string,
    year: number
}

interface AudioPlayer{
    audioVolume: number,
    songDuration: number,
    song: string,
    details:Details
}

const audioPlayer: AudioPlayer = {
    audioVolume: 90,
    songDuration: 36,
    song: "One",
    details: {
        author: 'Metallica',
        year: 1988
    }
}

const { audioVolume:volume, songDuration:duration, song:anotherSong, details } = audioPlayer;
const {author, year} = details;
console.log(`Author: ${author}, Year: ${year}`);
// console.table(audioPlayer)


const [,,trunks = 'Not Found']: string[] = ['Goku','Vegeta'];
console.error(`Personaje 3: `, trunks || 'No hay personaje')

export{}