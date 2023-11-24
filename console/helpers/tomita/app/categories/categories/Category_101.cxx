// Категория 101 - Бурение скважин

#encoding "utf-8"
#GRAMMAR_ROOT S

Fact -> "пробурить" | "бурение" | "скважина" | "ямобур";

S -> Fact interp(Category.Category_101);